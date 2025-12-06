<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('etapes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chantier_id')->constrained('chantiers')->onDelete('cascade');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->integer('ordre');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->enum('statut', ['non_commence', 'en_cours', 'termine'])->default('non_commence');
            $table->decimal('pourcentage', 5, 2)->default(0);
            $table->timestamps();

            $table->index('chantier_id');
            $table->index('ordre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etapes');
    }
};
