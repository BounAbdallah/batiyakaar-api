<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bail_id')->constrained('baux')->onDelete('cascade');
            $table->foreignId('locataire_id')->constrained('locataires')->onDelete('cascade');
            $table->foreignId('technicien_id')->nullable()->constrained('techniciens')->onDelete('set null');
            $table->string('titre');
            $table->text('description');
            $table->enum('categorie', ['plomberie', 'electricite', 'serrurerie', 'autre']);
            $table->enum('priorite', ['basse', 'moyenne', 'haute', 'urgente'])->default('moyenne');
            $table->enum('statut', ['ouvert', 'en_cours', 'resolu', 'ferme'])->default('ouvert');
            $table->timestamp('date_declaration');
            $table->timestamp('date_resolution')->nullable();
            $table->timestamps();

            $table->index('bail_id');
            $table->index('technicien_id');
            $table->index('statut');
            $table->index('priorite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
