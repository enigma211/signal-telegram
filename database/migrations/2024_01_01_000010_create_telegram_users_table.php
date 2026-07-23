<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_id')->unique();
            $table->enum('bot_language', ['fa', 'en']);
            $table->enum('subscription_tier', ['free', 'vip_forex', 'vip_crypto', 'vip_full'])
                ->default('free');
            $table->timestamp('vip_expires_at')->nullable();
            $table->string('referral_code')->unique();
            $table->foreignId('referred_by')
                ->nullable()
                ->constrained('telegram_users')
                ->nullOnDelete();
            $table->string('crypto_wallet_address')->nullable();
            $table->boolean('has_used_free_trial')->default(false);
            $table->timestamps();

            $table->index('bot_language');
            $table->index('subscription_tier');
            $table->index('vip_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_users');
    }
};
