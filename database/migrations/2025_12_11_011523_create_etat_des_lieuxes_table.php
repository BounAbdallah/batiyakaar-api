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
        Schema::create('etat_des_lieuxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bail_id')->constrained('baux')->onDelete('cascade');
            $table->enum('type', ['entrant', 'sortant']);
            $table->date('date_etat_des_lieux');
            $table->text('observations')->nullable();
            $table->json('content')->nullable(); // Structured data for rooms, keys, meters
            $table->json('documents')->nullable(); // Photos, PDFs
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etat_des_lieuxes');
    }
};
