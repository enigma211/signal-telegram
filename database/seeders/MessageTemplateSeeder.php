<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'signal',
                'name' => 'قالب سیگنال جدید',
                'placeholders_help' => '{symbol} {market} {entry_price} {tp1} {tp2_line} {tp3_line} {stop_loss}',
                'body_fa' => <<<'TXT'
📡 *سیگنال جدید هوش مصنوعی*
🏷 بازار: *{market}*
💎 نماد: *{symbol}*

🎯 ورود: `{entry_price}`
✅ TP1: `{tp1}`
{tp2_line}
{tp3_line}
🛑 استاپ‌لاس: `{stop_loss}`

_مدیریت سرمایه را رعایت کنید._
TXT,
                'body_en' => <<<'TXT'
📡 *New AI Signal*
🏷 Market: *{market}*
💎 Symbol: *{symbol}*

🎯 Entry: `{entry_price}`
✅ TP1: `{tp1}`
{tp2_line}
{tp3_line}
🛑 Stop Loss: `{stop_loss}`

_Always manage your risk._
TXT,
            ],
            [
                'key' => 'signal_update',
                'name' => 'قالب آپدیت سیگنال',
                'placeholders_help' => '{symbol} {market} {message}',
                'body_fa' => <<<'TXT'
🔄 *آپدیت سیگنال {symbol}*
🏷 {market}

{message}
TXT,
                'body_en' => <<<'TXT'
🔄 *Signal Update — {symbol}*
🏷 {market}

{message}
TXT,
            ],
            [
                'key' => 'signal_result',
                'name' => 'قالب نتیجه سیگنال',
                'placeholders_help' => '{symbol} {market} {result} {result_emoji} {pips_line}',
                'body_fa' => <<<'TXT'
{result_emoji} *نتیجه سیگنال {symbol}*
🏷 بازار: *{market}*
📈 نتیجه: *{result}*
{pips_line}
TXT,
                'body_en' => <<<'TXT'
{result_emoji} *Signal Result — {symbol}*
🏷 Market: *{market}*
📈 Result: *{result}*
{pips_line}
TXT,
            ],
        ];

        foreach ($templates as $template) {
            MessageTemplate::query()->updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}
