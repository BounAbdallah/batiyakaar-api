<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('biens', function (Blueprint $table) {
            // Drop the unique constraint only.
            // Regular index 'biens_reference_index' already exists from creation migration.
            try {
                $table->dropUnique('biens_reference_unique');
            } catch (\Exception $e) {
                // If index already dropped in previous failed run, ignore.
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biens', function (Blueprint $table) {
            // Restore unique constraint
            // We use the standard name to match original state
            try {
                $table->unique('reference');
            } catch (\Exception $e) {
            }
        });
    }
};
