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
        // 1. Backfill numero_quittance from numero if needed
        if (Schema::hasColumn('quittances', 'numero') && Schema::hasColumn('quittances', 'numero_quittance')) {
            DB::statement('UPDATE quittances SET numero_quittance = numero WHERE numero_quittance IS NULL');
        }

        // 2. Drop the numero column
        Schema::table('quittances', function (Blueprint $table) {
            if (Schema::hasColumn('quittances', 'numero')) {
                // Drop index first for SQLite compatibility
                try {
                    $table->dropIndex(['numero']);
                } catch (\Exception $e) {
                }
                try {
                    $table->dropUnique(['numero']);
                } catch (\Exception $e) {
                }

                $table->dropColumn('numero');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quittances', function (Blueprint $table) {
            if (!Schema::hasColumn('quittances', 'numero')) {
                $table->string('numero')->nullable(); // Make nullable on restore to avoid strict issues
            }
        });

        if (Schema::hasColumn('quittances', 'numero') && Schema::hasColumn('quittances', 'numero_quittance')) {
            DB::statement('UPDATE quittances SET numero = numero_quittance WHERE numero IS NULL');
        }
    }
};
