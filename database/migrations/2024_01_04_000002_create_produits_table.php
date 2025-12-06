<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogue_id')->constrained('catalogues')->onDelete('cascade');
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->onDelete('cascade');
            $table->string('reference');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('categorie');
            $table->decimal('prix_unitaire', 15, 2);
            $table->enum('unite', ['piece', 'm2', 'm3', 'kg', 'tonne', 'sac']);
            $table->integer('stock_disponible')->default(0);
            $table->text('url_image')->nullable();
            $table->timestamps();

            $table->index('catalogue_id');
            $table->index('fournisseur_id');
            $table->index('categorie');
            $table->unique(['fournisseur_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
