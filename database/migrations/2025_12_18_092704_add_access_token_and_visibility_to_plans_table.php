<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('est_public')->default(true)->after('est_personnalise');
            $table->string('access_token', 64)->nullable()->unique()->after('est_public');
            $table->timestamp('token_expires_at')->nullable()->after('access_token');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['est_public', 'access_token', 'token_expires_at']);
        });
    }
};
