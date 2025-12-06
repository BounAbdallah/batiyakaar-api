<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rapports_chantier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_construction_id')->constrained('projets_construction')->onDelete('cascade');
            $table->timestamp('date_generation');
            $table->enum('type_fichier', ['pdf', 'excel']);
            $table->text('url_pdf');
            $table->longText('contenu')->nullable(); // JSON
            $table->timestamps();

            $table->index('projet_construction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapports_chantier');
    }
};
