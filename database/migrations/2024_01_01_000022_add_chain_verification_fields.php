<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('chain_verified_at')->nullable()->after('admin_note');
            $table->timestamp('chain_verification_failed_at')->nullable()->after('chain_verified_at');
            $table->text('chain_verification_note')->nullable()->after('chain_verification_failed_at');
        });

        Schema::table('payment_settings', function (Blueprint $table) {
            $table->boolean('auto_confirm_verified_tx')->default(true)->after('referral_percent');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'chain_verified_at',
                'chain_verification_failed_at',
                'chain_verification_note',
            ]);
        });

        Schema::table('payment_settings', function (Blueprint $table) {
            $table->dropColumn('auto_confirm_verified_tx');
        });
    }
};
