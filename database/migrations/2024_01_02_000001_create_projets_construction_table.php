<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projets_construction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bailleur_id')->constrained('bailleurs')->onDelete('cascade');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->text('adresse');
            $table->decimal('budget_total', 15, 2);
            $table->decimal('budget_consomme', 15, 2)->default(0);
            $table->date('date_debut');
            $table->date('date_fin_prevue');
            $table->enum('statut', ['en_cours', 'termine', 'suspendu', 'annule'])->default('en_cours');
            $table->decimal('pourcentage_avancement', 5, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('bailleur_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projets_construction');
    }
};
