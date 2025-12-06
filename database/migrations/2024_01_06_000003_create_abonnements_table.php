<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('abonnements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agence_id')->constrained('agences')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->enum('statut', ['actif', 'expire', 'suspendu', 'annule'])->default('actif');
            $table->boolean('auto_renouvellement')->default(false);
            $table->timestamps();

            $table->index('agence_id');
            $table->index('plan_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnements');
    }
};
