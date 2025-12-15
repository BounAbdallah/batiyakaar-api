<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE abonnements MODIFY COLUMN statut ENUM('actif', 'expire', 'suspendu', 'annule', 'en_attente') DEFAULT 'en_attente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Warning: This could result in data loss if 'en_attente' values exist
        DB::statement("ALTER TABLE abonnements MODIFY COLUMN statut ENUM('actif', 'expire', 'suspendu', 'annule') DEFAULT 'actif'");
    }
};
