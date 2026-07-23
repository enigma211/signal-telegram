<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_bots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('language', ['fa', 'en'])->unique();
            $table->text('bot_token');
            $table->string('bot_username')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('webhook_set_at')->nullable();
            $table->timestamps();
        });

        Schema::create('telegram_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_bot_id')
                ->constrained('telegram_bots')
                ->cascadeOnDelete();
            $table->string('title');
            $table->string('chat_id');
            $table->string('username')->nullable();
            $table->enum('market_type', ['forex', 'crypto'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['telegram_bot_id', 'chat_id']);
            $table->index(['is_active', 'market_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_channels');
        Schema::dropIfExists('telegram_bots');
    }
};
