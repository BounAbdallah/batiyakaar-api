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
            // Drop the unique constraint
            // The index name is typically table_column_unique
            $table->dropUnique('biens_reference_unique');

            // Optionally add a non-unique index for performance
            $table->index('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biens', function (Blueprint $table) {
            $table->dropIndex(['reference']);
            $table->unique('reference');
        });
    }
};
