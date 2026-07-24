<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('twitter_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->string('context'); // signal, signal_result
            $table->nullableMorphs('related');
            $table->text('message_text')->nullable();
            $table->string('tweet_id')->nullable();
            $table->text('error_message')->nullable();
            $table->enum('status', ['failed', 'sent'])->default('failed');
            $table->unsignedInteger('attempts')->default(1);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('twitter_delivery_logs');
    }
};
