<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HashExistingPasswords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $users = DB::table('users')->select('id', 'password')->get();

        foreach ($users as $user) {
            // Si le mot de passe ne ressemble pas déjà à un hash bcrypt, on le hash.
            if (!Str::startsWith($user->password, '$2y$')) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'password' => Hash::make($user->password),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Par sécurité, on ne tente pas de retrouver les anciens mots de passe en clair.
        // Rien à faire ici.
    }
}



