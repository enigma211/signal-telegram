<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('wallet_trc20')->nullable();
            $table->string('wallet_bep20')->nullable();
            $table->string('default_network')->default('TRC20');
            $table->decimal('price_forex', 18, 2)->default(49);
            $table->decimal('price_crypto', 18, 2)->default(49);
            $table->decimal('price_full', 18, 2)->default(79);
            $table->unsignedInteger('subscription_days')->default(30);
            $table->unsignedInteger('free_trial_days')->default(3);
            $table->unsignedTinyInteger('referral_percent')->default(10);
            $table->string('currency')->default('USDT');
            $table->timestamps();
        });

        Schema::table('telegram_users', function (Blueprint $table) {
            $table->string('bot_state')->nullable()->after('has_used_free_trial');
            $table->json('bot_state_payload')->nullable()->after('bot_state');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('subscription_tier')->nullable()->after('type');
            $table->foreignId('promo_code_id')
                ->nullable()
                ->after('subscription_tier')
                ->constrained('promo_codes')
                ->nullOnDelete();
            $table->decimal('original_amount', 18, 8)->nullable()->after('promo_code_id');
            $table->unsignedTinyInteger('discount_percentage')->nullable()->after('original_amount');
            $table->text('admin_note')->nullable()->after('discount_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promo_code_id');
            $table->dropColumn([
                'subscription_tier',
                'original_amount',
                'discount_percentage',
                'admin_note',
            ]);
        });

        Schema::table('telegram_users', function (Blueprint $table) {
            $table->dropColumn(['bot_state', 'bot_state_payload']);
        });

        Schema::dropIfExists('payment_settings');
    }
};
