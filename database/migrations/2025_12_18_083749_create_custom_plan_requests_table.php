<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('custom_plan_requests', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email');
            $table->string('telephone')->nullable();
            $table->string('entreprise')->nullable();
            $table->integer('nombre_biens');
            $table->integer('nombre_utilisateurs');
            $table->json('fonctionnalites_souhaitees');
            $table->text('besoins_specifiques')->nullable();
            $table->decimal('budget_mensuel', 10, 2)->nullable();
            $table->enum('statut', ['en_attente', 'en_cours', 'approuve', 'refuse'])->default('en_attente');
            $table->foreignId('plan_id')->nullable()->constrained('plans')->onDelete('set null');
            $table->text('notes_admin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_plan_requests');
    }
};
