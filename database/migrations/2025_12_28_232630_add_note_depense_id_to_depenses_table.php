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
        Schema::table('depenses', function (Blueprint $table) {
            $table->foreignId('note_depense_id')->nullable()->after('id')->constrained('note_depenses')->onDelete('cascade');

            // Make these nullable as they can be inherited from the parent note
            $table->unsignedBigInteger('agence_id')->nullable()->change();
            $table->unsignedBigInteger('bailleur_id')->nullable()->change();
            $table->unsignedBigInteger('immeuble_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            $table->dropForeign(['note_depense_id']);
            $table->dropColumn('note_depense_id');

            $table->unsignedBigInteger('agence_id')->nullable(false)->change();
            $table->unsignedBigInteger('bailleur_id')->nullable(false)->change();
        });
    }
};
