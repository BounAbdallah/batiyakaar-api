<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('titre');
            $table->text('message');
            $table->enum('type', ['whatsapp', 'email', 'sms', 'systeme', 'bail_expire', 'bail_expiration_7j', 'bail_expiration_30j', 'paiement', 'paiement_retard', 'paiement_partiel', 'incident']);
            $table->timestamp('date_envoi');
            $table->boolean('lue')->default(false);
            $table->json('metadata')->nullable(); // Channel-specific data
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
            $table->index('lue');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
