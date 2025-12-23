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
        Schema::table('biens', function (Blueprint $table) {
            // Composition détaillée
            $table->integer('nombre_chambres')->nullable()->after('nombre_pieces');
            $table->integer('nombre_salons')->nullable()->after('nombre_chambres');
            $table->integer('nombre_cuisines')->nullable()->after('nombre_salons');
            $table->integer('nombre_salles_bain')->nullable()->after('nombre_cuisines');
            $table->integer('nombre_toilettes')->nullable()->after('nombre_salles_bain');
            $table->integer('nombre_balcons')->nullable()->after('nombre_toilettes');
            $table->integer('nombre_terrasses')->nullable()->after('nombre_balcons');
            $table->integer('nombre_parkings')->nullable()->after('nombre_terrasses');

            // Équipements et caractéristiques
            $table->boolean('meuble')->default(false)->after('nombre_parkings');
            $table->boolean('climatisation')->default(false)->after('meuble');
            $table->boolean('jardin')->default(false)->after('climatisation');
            $table->boolean('piscine')->default(false)->after('jardin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biens', function (Blueprint $table) {
            $table->dropColumn([
                'nombre_chambres',
                'nombre_salons',
                'nombre_cuisines',
                'nombre_salles_bain',
                'nombre_toilettes',
                'nombre_balcons',
                'nombre_terrasses',
                'nombre_parkings',
                'meuble',
                'climatisation',
                'jardin',
                'piscine',
            ]);
        });
    }
};
