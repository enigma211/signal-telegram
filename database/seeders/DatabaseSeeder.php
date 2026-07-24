<?php

namespace Database\Seeders;

use App\Models\PaymentSetting;
use App\Models\TwitterSetting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        PaymentSetting::current();
        TwitterSetting::current();

        $this->call([
            MessageTemplateSeeder::class,
            BotUxMessageTemplateSeeder::class,
        ]);
    }
}
