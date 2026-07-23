<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->enum('market_type', ['forex', 'crypto']);
            $table->string('symbol');
            $table->string('entry_price');
            $table->string('tp1');
            $table->string('tp2')->nullable();
            $table->string('tp3')->nullable();
            $table->string('stop_loss');
            $table->string('image_path')->nullable();
            $table->enum('target_audience', ['all', 'vip_only'])->default('vip_only');
            $table->enum('status', ['pending', 'broadcasted'])->default('pending');
            $table->enum('result', ['pending', 'win', 'loss', 'neutral'])->default('pending');
            $table->integer('pips_gained')->nullable();
            $table->timestamps();

            $table->index('market_type');
            $table->index('status');
            $table->index('result');
            $table->index(['market_type', 'target_audience']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signals');
    }
};
