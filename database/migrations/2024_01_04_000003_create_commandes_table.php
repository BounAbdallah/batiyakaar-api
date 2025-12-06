<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bailleur_id')->constrained('bailleurs')->onDelete('cascade');
            $table->foreignId('projet_construction_id')->nullable()->constrained('projets_construction')->onDelete('set null');
            $table->string('numero_commande')->unique();
            $table->timestamp('date_commande');
            $table->decimal('montant_total', 15, 2);
            $table->enum('statut', ['en_attente', 'confirmee', 'preparee', 'livree', 'annulee'])->default('en_attente');
            $table->timestamps();

            $table->index('bailleur_id');
            $table->index('projet_construction_id');
            $table->index('statut');
            $table->index('numero_commande');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
