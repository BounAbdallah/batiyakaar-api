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
            $table->dateTime('date_paiement')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paiements_loyer', function (Blueprint $table) {
            // Reverting to date might result in data loss for time, but it's the reverse op.
            $table->date('date_paiement')->nullable()->change();
        });
    }
};
