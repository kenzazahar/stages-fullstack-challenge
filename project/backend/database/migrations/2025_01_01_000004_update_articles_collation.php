<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateArticlesCollation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Passe la table articles en utf8mb4 avec une collation accent-insensible
        // sans recréer la table ni supprimer les données.
        DB::statement("ALTER TABLE articles CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revenir à la configuration précédente (latin1) si nécessaire.
        DB::statement("ALTER TABLE articles CONVERT TO CHARACTER SET latin1 COLLATE latin1_general_ci");
    }
}



