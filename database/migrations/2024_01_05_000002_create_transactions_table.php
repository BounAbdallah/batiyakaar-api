<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('emetteur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('beneficiaire_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('montant', 15, 2);
            $table->enum('type', ['escrow', 'loyer', 'commande', 'commission', 'retrait', 'depot']);
            $table->enum('statut', ['en_attente', 'reussie', 'echouee', 'annulee', 'remboursee'])->default('en_attente');
            $table->enum('mode_paiement', ['wave', 'orange_money', 'free_money', 'virement', 'especes'])->nullable();
            $table->timestamp('date_transaction');
            $table->timestamps();

            $table->index('emetteur_id');
            $table->index('beneficiaire_id');
            $table->index('type');
            $table->index('statut');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
