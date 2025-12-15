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
            if (!Schema::hasColumn('biens', 'immeuble_id')) {
                $table->foreignId('immeuble_id')->nullable()->constrained('immeubles')->onDelete('set null');
            }
            if (!Schema::hasColumn('biens', 'etage_id')) {
                $table->foreignId('etage_id')->nullable()->constrained('etages')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biens', function (Blueprint $table) {
            $table->dropForeign(['immeuble_id']);
            $table->dropForeign(['etage_id']);
            $table->dropColumn(['immeuble_id', 'etage_id']);
        });
    }
};
