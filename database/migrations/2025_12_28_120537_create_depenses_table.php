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
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->decimal('montant', 15, 2);
            $table->date('date_depense');
            $table->enum('categorie', ['electricite', 'eau', 'gardiennage', 'entretien', 'reparation', 'autre']);
            $table->enum('statut', ['paye', 'en_attente', 'annule'])->default('paye');
            $table->string('preuve')->nullable();

            // Relationships
            $table->foreignId('agence_id')->constrained('agences')->onDelete('cascade');
            $table->foreignId('bailleur_id')->constrained('bailleurs')->onDelete('cascade');
            $table->foreignId('immeuble_id')->nullable()->constrained('immeubles')->onDelete('set null');
            $table->foreignId('bien_id')->nullable()->constrained('biens')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
