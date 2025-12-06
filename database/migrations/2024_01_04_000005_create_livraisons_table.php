<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('livraisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->unique()->constrained('commandes')->onDelete('cascade');
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->onDelete('cascade');
            $table->date('date_livraison_prevue');
            $table->date('date_livraison_effective')->nullable();
            $table->text('adresse_livraison');
            $table->enum('statut', ['planifiee', 'en_cours', 'livree', 'probleme'])->default('planifiee');
            $table->text('url_preuve')->nullable();
            $table->timestamps();

            $table->index('commande_id');
            $table->index('fournisseur_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livraisons');
    }
};
