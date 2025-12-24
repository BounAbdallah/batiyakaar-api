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
        Schema::table('quittances', function (Blueprint $table) {
            // Check and add columns only if they don't exist
            if (!Schema::hasColumn('quittances', 'numero_quittance')) {
                $table->string('numero_quittance')->nullable()->after('paiement_loyer_id');
            }

            if (!Schema::hasColumn('quittances', 'montant')) {
                $table->decimal('montant', 10, 2)->nullable()->after('paiement_loyer_id');
            }

            if (!Schema::hasColumn('quittances', 'periode_debut')) {
                $table->date('periode_debut')->nullable()->after('paiement_loyer_id');
            }

            if (!Schema::hasColumn('quittances', 'periode_fin')) {
                $table->date('periode_fin')->nullable()->after('paiement_loyer_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quittances', function (Blueprint $table) {
            if (Schema::hasColumn('quittances', 'montant')) {
                $table->dropColumn('montant');
            }
            if (Schema::hasColumn('quittances', 'periode_debut')) {
                $table->dropColumn('periode_debut');
            }
            if (Schema::hasColumn('quittances', 'periode_fin')) {
                $table->dropColumn('periode_fin');
            }
            if (Schema::hasColumn('quittances', 'numero_quittance')) {
                $table->dropColumn('numero_quittance');
            }
        });
    }
};
