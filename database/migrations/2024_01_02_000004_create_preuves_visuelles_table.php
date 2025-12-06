<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('preuves_visuelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etape_id')->constrained('etapes')->onDelete('cascade');
            $table->foreignId('entrepreneur_id')->constrained('entrepreneurs')->onDelete('cascade');
            $table->enum('type', ['photo', 'video']);
            $table->text('url_fichier');
            $table->timestamp('horodatage');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('hash_certification', 64); // SHA256
            $table->boolean('validee')->default(false);
            $table->timestamps();

            $table->index('etape_id');
            $table->index('entrepreneur_id');
            $table->index('validee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preuves_visuelles');
    }
};
