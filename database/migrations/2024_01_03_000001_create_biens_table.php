<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('biens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bailleur_id')->constrained('bailleurs')->onDelete('cascade');
            $table->foreignId('agence_id')->nullable()->constrained('agences')->onDelete('set null');
            $table->foreignId('projet_construction_id')->nullable()->constrained('projets_construction')->onDelete('set null');
            $table->string('reference')->unique();
            $table->text('adresse');
            $table->enum('type', ['appartement', 'maison', 'studio', 'commerce']);
            $table->integer('nombre_pieces');
            $table->decimal('surface', 8, 2);
            $table->decimal('loyer_mensuel', 15, 2);
            $table->enum('statut', ['disponible', 'loue', 'maintenance', 'indisponible'])->default('disponible');
            $table->timestamps();
            $table->softDeletes();

            $table->index('bailleur_id');
            $table->index('agence_id');
            $table->index('statut');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biens');
    }
};
