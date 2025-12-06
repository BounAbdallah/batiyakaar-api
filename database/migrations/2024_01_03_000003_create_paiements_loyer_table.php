<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('paiements_loyer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bail_id')->constrained('baux')->onDelete('cascade');
            $table->decimal('montant', 15, 2);
            $table->date('date_paiement')->nullable();
            $table->date('date_prevue');
            $table->enum('mode_paiement', ['wave', 'orange_money', 'free_money'])->nullable();
            $table->enum('statut', ['en_attente', 'paye', 'en_retard', 'annule'])->default('en_attente');
            $table->string('reference_transaction')->unique()->nullable();
            $table->timestamps();

            $table->index('bail_id');
            $table->index('statut');
            $table->index('date_prevue');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements_loyer');
    }
};
