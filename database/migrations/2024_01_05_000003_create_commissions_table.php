<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->decimal('taux', 5, 2); // Percentage
            $table->decimal('montant', 15, 2);
            $table->enum('type', ['plateforme_escrow', 'plateforme_loyer', 'plateforme_marketplace', 'agence']);
            $table->enum('beneficiaire', ['plateforme', 'agence']);
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
