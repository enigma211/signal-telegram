<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false)->after('bot_state_payload');
            $table->timestamp('blocked_at')->nullable()->after('is_blocked');
            $table->string('blocked_reason')->nullable()->after('blocked_at');

            $table->index('is_blocked');
        });
    }

    public function down(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->dropIndex(['is_blocked']);
            $table->dropColumn(['is_blocked', 'blocked_at', 'blocked_reason']);
        });
    }
};
