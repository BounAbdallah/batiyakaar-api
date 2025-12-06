<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('paiements_escrow', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_construction_id')->constrained('projets_construction')->onDelete('cascade');
            $table->foreignId('entrepreneur_id')->constrained('entrepreneurs')->onDelete('cascade');
            $table->decimal('montant', 15, 2);
            $table->timestamp('date_depot');
            $table->timestamp('date_deblocage')->nullable();
            $table->enum('statut', ['en_attente', 'debloque', 'annule', 'rembourse'])->default('en_attente');
            $table->text('condition_deblocage')->nullable();
            $table->timestamps();

            $table->index('projet_construction_id');
            $table->index('entrepreneur_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements_escrow');
    }
};
