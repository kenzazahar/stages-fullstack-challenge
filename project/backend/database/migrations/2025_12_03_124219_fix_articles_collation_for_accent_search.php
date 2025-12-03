<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convertir la table complète en utf8mb4_unicode_ci
        DB::statement('ALTER TABLE articles CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        
        // Modifier explicitement les colonnes de recherche
        Schema::table('articles', function (Blueprint $table) {
            $table->string('title', 255)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
            $table->text('content')->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retour à la collation d'origine
        DB::statement('ALTER TABLE articles CONVERT TO CHARACTER SET latin1 COLLATE latin1_general_ci');
        
        Schema::table('articles', function (Blueprint $table) {
            $table->string('title', 255)->charset('latin1')->collation('latin1_general_ci')->change();
            $table->text('content')->charset('latin1')->collation('latin1_general_ci')->change();
        });
    }
};