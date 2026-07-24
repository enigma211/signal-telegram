<?php

namespace App\Services;

use App\Enums\BotLanguage;
use App\Enums\SignalResult;
use App\Enums\TargetAudience;
use App\Models\MessageTemplate;
use App\Models\Signal;
use App\Models\SignalUpdate;
use App\Models\TelegramUser;
use Illuminate\Database\Eloquent\Builder;

class SignalMessageBuilder
{
    public function signal(Signal $signal, BotLanguage $language): string
    {
        $template = MessageTemplate::findByKey('signal');

        if (! $template) {
            return $this->fallbackSignal($signal, $language);
        }

        $isFa = $language === BotLanguage::Fa;
        $market = $isFa
            ? $signal->market_type->label()
            : strtoupper($signal->market_type->value);

        return $template->render($language, [
            'symbol' => $signal->symbol,
            'market' => $market,
            'entry_price' => $signal->entry_price,
            'tp1' => $signal->tp1,
            'tp2_line' => filled($signal->tp2)
                ? ($isFa ? "✅ TP2: `{$signal->tp2}`" : "✅ TP2: `{$signal->tp2}`")
                : '',
            'tp3_line' => filled($signal->tp3)
                ? ($isFa ? "✅ TP3: `{$signal->tp3}`" : "✅ TP3: `{$signal->tp3}`")
                : '',
            'stop_loss' => $signal->stop_loss,
        ]);
    }

    public function update(Signal $signal, SignalUpdate $update, BotLanguage $language): string
    {
        $template = MessageTemplate::findByKey('signal_update');
        $message = $update->messageFor($language);
        $market = $language === BotLanguage::Fa
            ? $signal->market_type->label()
            : strtoupper($signal->market_type->value);

        if (! $template) {
            $header = $language === BotLanguage::Fa
                ? "🔄 *آپدیت سیگنال {$signal->symbol}*"
                : "🔄 *Signal Update — {$signal->symbol}*";

            return "{$header}\n🏷 {$market}\n\n{$message}";
        }

        return $template->render($language, [
            'symbol' => $signal->symbol,
            'market' => $market,
            'message' => $message,
        ]);
    }

    public function result(Signal $signal, BotLanguage $language): string
    {
        $template = MessageTemplate::findByKey('signal_result');
        $isFa = $language === BotLanguage::Fa;

        [$emoji, $label] = match ($signal->result) {
            SignalResult::Win => ['✅', $isFa ? 'برد' : 'WIN'],
            SignalResult::Loss => ['❌', $isFa ? 'باخت' : 'LOSS'],
            SignalResult::Neutral => ['➖', $isFa ? 'خنثی' : 'NEUTRAL'],
            default => ['⏳', $isFa ? 'در انتظار' : 'PENDING'],
        };

        $market = $isFa
            ? $signal->market_type->label()
            : strtoupper($signal->market_type->value);

        $pipsLine = $signal->pips_gained !== null
            ? ($isFa ? "📊 پیپ: *{$signal->pips_gained}*" : "📊 Pips: *{$signal->pips_gained}*")
            : '';

        if (! $template) {
            return $this->fallbackResult($signal, $language, $emoji, $label, $market, $pipsLine);
        }

        return $template->render($language, [
            'symbol' => $signal->symbol,
            'market' => $market,
            'result' => $label,
            'result_emoji' => $emoji,
            'pips_line' => $pipsLine,
        ]);
    }

    public function recipientsFor(Signal $signal): Builder
    {
        // all = free + VIP (promo/public signals)
        // vip_only = paid VIP users for that market
        $query = TelegramUser::query()->notBlocked();

        if ($signal->target_audience === TargetAudience::VipOnly) {
            $query->eligibleForMarket($signal->market_type);
        }

        return $query->orderBy('id');
    }

    protected function fallbackSignal(Signal $signal, BotLanguage $language): string
    {
        $isFa = $language === BotLanguage::Fa;
        $market = $isFa
            ? $signal->market_type->label()
            : strtoupper($signal->market_type->value);

        $lines = $isFa
            ? [
                '📡 *سیگنال جدید هوش مصنوعی*',
                "🏷 بازار: *{$market}*",
                "💎 نماد: *{$signal->symbol}*",
                '',
                "🎯 ورود: `{$signal->entry_price}`",
                "✅ TP1: `{$signal->tp1}`",
            ]
            : [
                '📡 *New AI Signal*',
                "🏷 Market: *{$market}*",
                "💎 Symbol: *{$signal->symbol}*",
                '',
                "🎯 Entry: `{$signal->entry_price}`",
                "✅ TP1: `{$signal->tp1}`",
            ];

        if (filled($signal->tp2)) {
            $lines[] = "✅ TP2: `{$signal->tp2}`";
        }

        if (filled($signal->tp3)) {
            $lines[] = "✅ TP3: `{$signal->tp3}`";
        }

        $lines[] = $isFa
            ? "🛑 استاپ‌لاس: `{$signal->stop_loss}`"
            : "🛑 Stop Loss: `{$signal->stop_loss}`";
        $lines[] = '';
        $lines[] = $isFa ? '_مدیریت سرمایه را رعایت کنید._' : '_Always manage your risk._';

        return implode("\n", $lines);
    }

    protected function fallbackResult(
        Signal $signal,
        BotLanguage $language,
        string $emoji,
        string $label,
        string $market,
        string $pipsLine
    ): string {
        if ($language === BotLanguage::Fa) {
            return "{$emoji} *نتیجه سیگنال {$signal->symbol}*\n"
                ."🏷 بازار: *{$market}*\n"
                ."📈 نتیجه: *{$label}*\n"
                .$pipsLine;
        }

        return "{$emoji} *Signal Result — {$signal->symbol}*\n"
            ."🏷 Market: *{$market}*\n"
            ."📈 Result: *{$label}*\n"
            .$pipsLine;
    }
}
