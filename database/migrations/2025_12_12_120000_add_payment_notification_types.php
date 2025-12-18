<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add payment-related notification types
        try {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('whatsapp', 'email', 'sms', 'systeme', 'bail_expire', 'bail_expiration_7j', 'bail_expiration_30j', 'paiement', 'paiement_retard', 'paiement_partiel', 'incident')");
        } catch (\Exception $e) {
            // SQLite does not support MODIFY COLUMN
        }
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('whatsapp', 'email', 'sms', 'systeme', 'bail_expire', 'bail_expiration_7j', 'bail_expiration_30j', 'paiement', 'incident')");
    }
};
