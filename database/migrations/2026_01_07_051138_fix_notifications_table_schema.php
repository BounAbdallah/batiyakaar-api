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
        Schema::table('notifications', function (Blueprint $table) {
            // Change ENUM to String to support any notification class/type
            $table->string('type', 255)->change();

            // Add columns to support standard Laravel Database Notifications if they don't exist
            // This allows the table to be hybrid (Custom + Standard)
            if (!Schema::hasColumn('notifications', 'notifiable_type')) {
                $table->string('notifiable_type')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('notifications', 'notifiable_id')) {
                $table->unsignedBigInteger('notifiable_id')->nullable()->after('notifiable_type');
            }
            if (!Schema::hasColumn('notifications', 'data')) {
                $table->text('data')->nullable()->after('message');
            }
            if (!Schema::hasColumn('notifications', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('lue');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Reverting complex changes is hard, but we can try
            // We won't revert the string change as data might be lost
            if (Schema::hasColumn('notifications', 'notifiable_type')) {
                $table->dropColumn(['notifiable_type', 'notifiable_id', 'data', 'read_at']);
            }
        });
    }
};
