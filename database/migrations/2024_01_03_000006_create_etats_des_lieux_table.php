<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('etats_des_lieux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bail_id')->constrained('baux')->onDelete('cascade');
            $table->enum('type', ['entrant', 'sortant']);
            $table->date('date');
            $table->longText('observations')->nullable();
            $table->text('url_photos')->nullable(); // JSON array
            $table->text('signature')->nullable();
            $table->timestamps();

            $table->index('bail_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etats_des_lieux');
    }
};
