<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bailleurs', function (Blueprint $table) {
            $table->foreignId('agence_id')->nullable()->after('user_id')
                ->constrained('agences')->onDelete('set null');
        });

        // Backfill: assign agence_id to existing bailleurs via their first bien
        DB::statement("
            UPDATE bailleurs b
            INNER JOIN biens bi ON bi.bailleur_id = b.id
            SET b.agence_id = bi.agence_id
            WHERE b.agence_id IS NULL AND bi.agence_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('bailleurs', function (Blueprint $table) {
            $table->dropForeign(['agence_id']);
            $table->dropColumn('agence_id');
        });
    }
};
