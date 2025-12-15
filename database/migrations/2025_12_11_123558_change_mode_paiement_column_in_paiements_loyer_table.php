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
        Schema::table('paiements_loyer', function (Blueprint $table) {
            // Drop enum constraint if needed or just update definition
            // Easier to just drop and recreate or use generic 'string'
            // Since there's existing data, we might need raw statement or doctrine/dbal
            // But for this dev env, let's just use string() and change method if supported
            $table->string('mode_paiement')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paiements_loyer', function (Blueprint $table) {
            $table->enum('mode_paiement', ['wave', 'orange_money', 'free_money'])->change();
        });
    }
};
