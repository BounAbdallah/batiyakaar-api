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
            $table->date('date_debut_mandat')->nullable()->after('duree_mandat')->comment('Date de début du mandat');
            $table->date('date_fin_mandat')->nullable()->after('date_debut_mandat')->comment('Date de fin du mandat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('immeubles', function (Blueprint $table) {
            $table->dropColumn(['date_debut_mandat', 'date_fin_mandat']);
        });
    }
};
