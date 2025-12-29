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
        Schema::create('note_depenses', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->integer('mois');
            $table->integer('annee');
            $table->text('description')->nullable();
            $table->decimal('total_montant', 15, 2)->default(0);
            $table->enum('statut', ['en_attente', 'paye', 'annule'])->default('en_attente');

            $table->foreignId('agence_id')->constrained('agences')->onDelete('cascade');
            $table->foreignId('bailleur_id')->constrained('bailleurs')->onDelete('cascade');
            $table->foreignId('immeuble_id')->nullable()->constrained('immeubles')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note_depenses');
    }
};
