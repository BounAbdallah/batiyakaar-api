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
        Schema::table('immeubles', function (Blueprint $table) {
            $table->string('type_mandat')->nullable()->after('taux_commission')->comment('Type de mandat: gerance_totale, recouvrement_seulement, declaration_impots');
            $table->integer('duree_mandat')->nullable()->after('type_mandat')->comment('Durée du mandat en mois');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immeubles', function (Blueprint $table) {
            $table->dropColumn(['type_mandat', 'duree_mandat']);
        });
    }
};
