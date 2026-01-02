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
        // Using raw SQL because Schema builder doesn't support changing enum options easily across all drivers
        // and identifying the correct syntax for MySQL/MariaDB
        DB::statement("ALTER TABLE biens MODIFY COLUMN type ENUM('appartement', 'maison', 'studio', 'commerce', 'villa', 'terrain', 'chambre') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Warning: This might fail if there are values that are not in the old list
        DB::statement("ALTER TABLE biens MODIFY COLUMN type ENUM('appartement', 'maison', 'studio', 'commerce') NOT NULL");
    }
};
