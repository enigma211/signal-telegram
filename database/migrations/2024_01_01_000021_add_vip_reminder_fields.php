<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->timestamp('vip_expiry_reminded_at')->nullable()->after('vip_expires_at');
        });

        Schema::table('payment_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('vip_reminder_days')->default(3)->after('subscription_days');
        });
    }

    public function down(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            $table->dropColumn('vip_expiry_reminded_at');
        });

        Schema::table('payment_settings', function (Blueprint $table) {
            $table->dropColumn('vip_reminder_days');
        });
    }
};
