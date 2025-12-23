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
        Schema::create('fonctionnalites', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Code unique de la fonctionnalité');
            $table->string('nom')->comment('Nom affiché de la fonctionnalité');
            $table->text('description')->nullable()->comment('Description de la fonctionnalité');
            $table->string('module')->nullable()->comment('Module associé (pour le sidebar)');
            $table->string('icone')->nullable()->comment('Nom de l\'icône Lucide React');
            $table->string('route')->nullable()->comment('Route frontend associée');
            $table->boolean('actif')->default(true)->comment('Statut actif/inactif');
            $table->integer('ordre')->default(0)->comment('Ordre d\'affichage dans le menu');
            $table->timestamps();

            $table->index('actif');
            $table->index('ordre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fonctionnalites');
    }
};
