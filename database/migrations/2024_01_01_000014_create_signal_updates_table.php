<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signal_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signal_id')
                ->constrained('signals')
                ->cascadeOnDelete();
            $table->string('update_message_fa');
            $table->string('update_message_en');
            $table->timestamps();

            $table->index('signal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signal_updates');
    }
};
