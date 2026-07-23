<?php

namespace Tests\Feature;

use App\Enums\MarketType;
use App\Enums\SignalResult;
use App\Enums\SignalStatus;
use App\Enums\TargetAudience;
use App\Jobs\BroadcastResultJob;
use App\Jobs\BroadcastSignalJob;
use App\Jobs\BroadcastSignalUpdateJob;
use App\Models\Signal;
use App\Models\SignalUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FilamentBroadcastDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_signal_via_model_flow_matches_api_broadcast(): void
    {
        Queue::fake();

        $signal = Signal::query()->create([
            'market_type' => MarketType::Forex,
            'symbol' => 'XAUUSD',
            'entry_price' => '2000',
            'tp1' => '2010',
            'stop_loss' => '1990',
            'target_audience' => TargetAudience::All,
            'status' => SignalStatus::Pending,
            'result' => SignalResult::Pending,
        ]);

        BroadcastSignalJob::dispatch($signal);

        Queue::assertPushed(BroadcastSignalJob::class);
    }

    public function test_signal_update_and_result_jobs_dispatch(): void
    {
        Queue::fake();

        $signal = Signal::query()->create([
            'market_type' => MarketType::Crypto,
            'symbol' => 'BTCUSDT',
            'entry_price' => '60000',
            'tp1' => '61000',
            'stop_loss' => '59000',
            'target_audience' => TargetAudience::VipOnly,
            'status' => SignalStatus::Broadcasted,
            'result' => SignalResult::Pending,
        ]);

        $update = SignalUpdate::query()->create([
            'signal_id' => $signal->id,
            'update_message_fa' => 'آپدیت',
            'update_message_en' => 'Update',
        ]);

        BroadcastSignalUpdateJob::dispatch($update);
        $signal->update(['result' => SignalResult::Win, 'pips_gained' => 100]);
        BroadcastResultJob::dispatch($signal->fresh());

        Queue::assertPushed(BroadcastSignalUpdateJob::class);
        Queue::assertPushed(BroadcastResultJob::class);
    }
}
