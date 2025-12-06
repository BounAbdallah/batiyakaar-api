<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paiement_loyer_id')->unique()->constrained('paiements_loyer')->onDelete('cascade');
            $table->string('numero')->unique();
            $table->timestamp('date_emission');
            $table->text('url_pdf');
            $table->timestamps();

            $table->index('numero');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quittances');
    }
};
