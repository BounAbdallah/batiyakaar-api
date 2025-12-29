<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Associate existing bailleurs with their agencies
        // Get agency_id from the bailleur's first bien using a subquery
        DB::statement("
            UPDATE users u
            SET u.agence_id = (
                SELECT bien.agence_id 
                FROM bailleurs b 
                INNER JOIN biens bien ON bien.bailleur_id = b.id 
                WHERE b.user_id = u.id 
                LIMIT 1
            )
            WHERE u.user_type = 'bailleur' 
            AND u.agence_id IS NULL
            AND EXISTS (
                SELECT 1 FROM bailleurs b2 
                INNER JOIN biens bien2 ON bien2.bailleur_id = b2.id 
                WHERE b2.user_id = u.id
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this - it's a data fix
    }
};
