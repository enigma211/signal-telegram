<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->string('context'); // signal, signal_update, signal_result, manual
            $table->nullableMorphs('related');
            $table->string('recipient_type'); // user, channel
            $table->string('chat_id');
            $table->foreignId('telegram_user_id')->nullable()->constrained('telegram_users')->nullOnDelete();
            $table->foreignId('telegram_channel_id')->nullable()->constrained('telegram_channels')->nullOnDelete();
            $table->string('bot_language', 5)->nullable();
            $table->text('message_text');
            $table->string('image_path')->nullable();
            $table->text('error_message')->nullable();
            $table->enum('status', ['failed', 'sent', 'abandoned'])->default('failed');
            $table->unsignedInteger('attempts')->default(1);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_delivery_logs');
    }
};
