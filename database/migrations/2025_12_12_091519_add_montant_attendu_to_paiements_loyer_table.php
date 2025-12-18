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
        Schema::table('paiements_loyer', function (Blueprint $table) {
            if (!Schema::hasColumn('paiements_loyer', 'montant_attendu')) {
                $table->decimal('montant_attendu', 15, 2)->nullable()->after('montant')->comment('Montant total du loyer attendu pour cette période');
            }
            if (!Schema::hasColumn('paiements_loyer', 'periode_debut')) {
                $table->date('periode_debut')->nullable()->after('date_prevue')->comment('Début de la période couverte par ce paiement');
            }
            if (!Schema::hasColumn('paiements_loyer', 'periode_fin')) {
                $table->date('periode_fin')->nullable()->after('periode_debut')->comment('Fin de la période couverte par ce paiement');
            }
        });

        // Modify statut enum to include 'partiel'
        try {
            DB::statement("ALTER TABLE paiements_loyer MODIFY COLUMN statut ENUM('en_attente', 'paye', 'partiel', 'impaye', 'annule') DEFAULT 'en_attente'");
        } catch (\Exception $e) {
            // SQLite does not support MODIFY COLUMN, so we ignore this error.
            // If strict mode is needed, a full table recreation is required for SQLite.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paiements_loyer', function (Blueprint $table) {
            $table->dropColumn(['montant_attendu', 'periode_debut', 'periode_fin']);
        });

        // Restore original statut enum
        DB::statement("ALTER TABLE paiements_loyer MODIFY COLUMN statut ENUM('en_attente', 'paye', 'impaye', 'annule') DEFAULT 'en_attente'");
    }
};
