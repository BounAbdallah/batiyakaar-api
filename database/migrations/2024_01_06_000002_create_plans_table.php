<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->text('description')->nullable();
            $table->decimal('prix_mensuel', 15, 2);
            $table->integer('limite_utilisateurs');
            $table->integer('limite_biens');
            $table->json('fonctionnalites')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index('actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
