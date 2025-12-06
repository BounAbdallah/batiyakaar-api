<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parties_prenantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_construction_id')->constrained('projets_construction')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role');
            $table->timestamp('date_ajout');
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['projet_construction_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties_prenantes');
    }
};
