<?php

namespace App\Jobs;

use App\Models\Signal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastSignalJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Signal $signal
    ) {}

    public function handle(): void
    {
        $signal = $this->signal->fresh();

        if (! $signal) {
            return;
        }

        BroadcastSignalLanguageJob::dispatch($signal, 'fa');
        BroadcastSignalLanguageJob::dispatch($signal, 'en');
        PostSignalToTwitterJob::dispatch($signal);

        $signal->markAsBroadcasted();
    }
}
