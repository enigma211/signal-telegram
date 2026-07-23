<?php

namespace App\Jobs;

use App\Enums\BotLanguage;
use App\Jobs\Concerns\BroadcastsToTelegramUsers;
use App\Models\Signal;
use App\Services\SignalMessageBuilder;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastSignalLanguageJob implements ShouldQueue
{
    use BroadcastsToTelegramUsers;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public Signal $signal,
        public string $language
    ) {
        $this->onQueue('telegram-'.$language);
    }

    public function handle(TelegramService $telegram, SignalMessageBuilder $builder): void
    {
        $signal = $this->signal->fresh();
        $botLanguage = BotLanguage::from($this->language);

        if (! $signal) {
            return;
        }

        $builder->recipientsFor($signal)
            ->language($botLanguage)
            ->chunkById(100, function ($users) use ($telegram, $builder, $signal, $botLanguage): void {
                foreach ($users as $user) {
                    $text = $builder->signal($signal, $botLanguage);
                    $this->sendFormattedMessage($telegram, $user, $text, $signal->image_path, 'signal', $signal);
                }
            });

        $this->broadcastToChannelsForLanguage(
            $telegram,
            $signal->market_type,
            $botLanguage,
            fn () => $builder->signal($signal, $botLanguage),
            $signal->image_path,
            'signal',
            $signal
        );
    }
}
