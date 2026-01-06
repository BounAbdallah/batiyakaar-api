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
        $bailleurs = DB::table('users')
            ->where('user_type', 'bailleur')
            ->whereNull('agence_id')
            ->get();

        foreach ($bailleurs as $user) {
            $bailleurId = DB::table('bailleurs')->where('user_id', $user->id)->value('id');

            if ($bailleurId) {
                $agenceId = DB::table('biens')
                    ->where('bailleur_id', $bailleurId)
                    ->whereNotNull('agence_id')
                    ->value('agence_id');

                if ($agenceId) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['agence_id' => $agenceId]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this - it's a data fix
    }
};
