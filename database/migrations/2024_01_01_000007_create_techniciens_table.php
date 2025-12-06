<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('techniciens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agence_id')->constrained('agences')->onDelete('cascade');
            $table->string('nom');
            $table->string('telephone', 20);
            $table->string('specialite');
            $table->timestamps();

            $table->index('agence_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('techniciens');
    }
};
