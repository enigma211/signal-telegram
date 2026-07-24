<?php

namespace App\Jobs;

use App\Enums\BotLanguage;
use App\Enums\TargetAudience;
use App\Models\Signal;
use App\Models\TwitterSetting;
use App\Services\SignalMessageBuilder;
use App\Services\TwitterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostSignalToTwitterJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Signal $signal
    ) {
        $this->onQueue('default');
    }

    public function handle(TwitterService $twitter, SignalMessageBuilder $builder): void
    {
        $settings = TwitterSetting::current();

        if (! $settings->isReady()) {
            return;
        }

        $signal = $this->signal->fresh();
        if (! $signal) {
            return;
        }

        if (! $settings->post_signals) {
            return;
        }

        if (! $settings->post_vip && $signal->target_audience === TargetAudience::VipOnly) {
            return;
        }

        $text = $twitter->toPlainText($builder->signal($signal, BotLanguage::En));
        $text = $this->appendCta($text, $settings, $twitter);

        try {
            $result = $twitter->post($text, $signal->image_path);
            Log::info('Signal posted to Twitter', [
                'signal_id' => $signal->id,
                'tweet_id' => $result['id'] ?? null,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed posting signal to Twitter', [
                'signal_id' => $signal->id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function appendCta(string $text, TwitterSetting $settings, TwitterService $twitter): string
    {
        $cta = $settings->resolvedCta();
        if ($cta === '') {
            return $text;
        }

        return $twitter->fitTweet(rtrim($text)."\n\n".$cta);
    }
}
