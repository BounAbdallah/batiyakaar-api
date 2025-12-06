<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ventilations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paiement_loyer_id')->unique()->constrained('paiements_loyer')->onDelete('cascade');
            $table->decimal('montant_agence', 15, 2);
            $table->decimal('montant_plateforme', 15, 2);
            $table->decimal('montant_bailleur', 15, 2);
            $table->timestamp('date_ventilation');
            $table->timestamps();

            $table->index('paiement_loyer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventilations');
    }
};
