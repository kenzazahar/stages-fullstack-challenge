<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ⚠️ IMPORTANT : Cette migration hash tous les mots de passe en clair
        // Elle détecte automatiquement si un mot de passe est déjà hashé
        
        $users = DB::table('users')->get();
        
        foreach ($users as $user) {
            // Vérifier si le mot de passe n'est PAS déjà hashé
            // Les hash bcrypt commencent toujours par "$2y$"
            if (!str_starts_with($user->password, '$2y$')) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'password' => Hash::make($user->password),
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ⚠️ ATTENTION : Il est IMPOSSIBLE de retrouver les mots de passe originaux
        // Cette migration est irréversible pour des raisons de sécurité
        // Les utilisateurs devront réinitialiser leurs mots de passe si rollback nécessaire
    }
};