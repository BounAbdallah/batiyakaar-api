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
        Schema::table('agences', function (Blueprint $table) {
            $table->decimal('taux_commission_agence', 5, 2)->default(10.00)->after('adresse')->comment('Taux de commission de l\'agence en %');
            $table->decimal('taux_commission_plateforme', 5, 2)->default(5.00)->after('taux_commission_agence')->comment('Taux de commission de la plateforme en %');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agences', function (Blueprint $table) {
            $table->dropColumn(['taux_commission_agence', 'taux_commission_plateforme']);
        });
    }
};
