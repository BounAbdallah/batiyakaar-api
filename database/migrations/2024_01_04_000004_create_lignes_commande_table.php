<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lignes_commande', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->foreignId('produit_id')->constrained('produits')->onDelete('cascade');
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 15, 2);
            $table->decimal('sous_total', 15, 2);
            $table->timestamps();

            $table->index('commande_id');
            $table->index('produit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_commande');
    }
};
