<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('baux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bien_id')->constrained('biens')->onDelete('cascade');
            $table->foreignId('locataire_id')->constrained('locataires')->onDelete('cascade');
            $table->foreignId('agence_id')->constrained('agences')->onDelete('cascade');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->decimal('loyer_mensuel', 15, 2);
            $table->decimal('caution', 15, 2);
            $table->enum('statut', ['actif', 'expire', 'resilie', 'renouvele'])->default('actif');
            $table->timestamps();

            $table->index('bien_id');
            $table->index('locataire_id');
            $table->index('agence_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baux');
    }
};
