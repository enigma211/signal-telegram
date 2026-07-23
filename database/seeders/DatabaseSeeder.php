<?php

namespace Database\Seeders;

use App\Models\PaymentSetting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        PaymentSetting::current();

        $this->call([
            MessageTemplateSeeder::class,
            BotUxMessageTemplateSeeder::class,
        ]);
    }
}
