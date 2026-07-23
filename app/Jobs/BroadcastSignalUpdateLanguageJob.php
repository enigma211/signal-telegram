<?php

namespace App\Jobs;

use App\Enums\BotLanguage;
use App\Jobs\Concerns\BroadcastsToTelegramUsers;
use App\Models\SignalUpdate;
use App\Services\SignalMessageBuilder;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastSignalUpdateLanguageJob implements ShouldQueue
{
    use BroadcastsToTelegramUsers;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public SignalUpdate $signalUpdate,
        public string $language
    ) {
        $this->onQueue('telegram-'.$language);
    }

    public function handle(TelegramService $telegram, SignalMessageBuilder $builder): void
    {
        $update = $this->signalUpdate->fresh(['signal']);
        $botLanguage = BotLanguage::from($this->language);

        if (! $update || ! $update->signal) {
            return;
        }

        $signal = $update->signal;

        $builder->recipientsFor($signal)
            ->language($botLanguage)
            ->chunkById(100, function ($users) use ($telegram, $builder, $signal, $update, $botLanguage): void {
                foreach ($users as $user) {
                    $text = $builder->update($signal, $update, $botLanguage);
                    $this->sendFormattedMessage($telegram, $user, $text, null, 'signal_update', $update);
                }
            });

        $this->broadcastToChannelsForLanguage(
            $telegram,
            $signal->market_type,
            $botLanguage,
            fn () => $builder->update($signal, $update, $botLanguage),
            null,
            'signal_update',
            $update
        );
    }
}
