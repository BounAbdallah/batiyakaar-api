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
        // Using raw SQL to modify ENUM because Doctrine DBAL might not be installed or configured for ENUMs
        try {
            DB::statement("ALTER TABLE users MODIFY COLUMN user_type ENUM('bailleur', 'agence', 'entrepreneur', 'fournisseur', 'locataire', 'admin', 'technicien') NOT NULL");
        } catch (\Exception $e) {
            // SQLite does not support MODIFY COLUMN
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum
        DB::statement("ALTER TABLE users MODIFY COLUMN user_type ENUM('bailleur', 'agence', 'entrepreneur', 'fournisseur', 'locataire') NOT NULL");
    }
};
