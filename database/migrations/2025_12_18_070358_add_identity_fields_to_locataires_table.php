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
        Schema::table('locataires', function (Blueprint $table) {
            $table->string('numero_cni')->nullable()->after('revenu_mensuel');
            $table->date('date_naissance')->nullable()->after('numero_cni');
            $table->string('lieu_naissance')->nullable()->after('date_naissance');
            $table->string('cni_recto')->nullable()->after('lieu_naissance');
            $table->string('cni_verso')->nullable()->after('cni_recto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locataires', function (Blueprint $table) {
            $table->dropColumn(['numero_cni', 'date_naissance', 'lieu_naissance', 'cni_recto', 'cni_verso']);
        });
    }
};
