<?php

namespace App\Jobs;

use App\Models\SignalUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastSignalUpdateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public SignalUpdate $signalUpdate
    ) {}

    public function handle(): void
    {
        $update = $this->signalUpdate->fresh();

        if (! $update) {
            return;
        }

        BroadcastSignalUpdateLanguageJob::dispatch($update, 'fa');
        BroadcastSignalUpdateLanguageJob::dispatch($update, 'en');
    }
}
