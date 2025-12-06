<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chantiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_construction_id')->unique()->constrained('projets_construction')->onDelete('cascade');
            $table->string('localisation');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamps();

            $table->index('projet_construction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chantiers');
    }
};
