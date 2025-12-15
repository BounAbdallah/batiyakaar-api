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
        Schema::table('baux', function (Blueprint $table) {
            $table->string('type_duree')->default('indeterminee')->after('date_fin'); // 'indeterminee' or 'determinee'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('baux', function (Blueprint $table) {
            $table->dropColumn('type_duree');
        });
    }
};
