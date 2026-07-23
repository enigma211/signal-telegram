<?php

namespace Tests\Feature;

use App\Enums\MarketType;
use App\Enums\SignalResult;
use App\Enums\SignalStatus;
use App\Enums\TargetAudience;
use App\Jobs\BroadcastResultJob;
use App\Jobs\BroadcastSignalJob;
use App\Jobs\BroadcastSignalLanguageJob;
use App\Jobs\BroadcastSignalUpdateJob;
use App\Models\Signal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PythonSignalApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.python_api.token' => 'test-api-token']);
    }

    public function test_rejects_requests_without_api_token(): void
    {
        $this->postJson('/api/signals', [
            'market_type' => 'forex',
            'symbol' => 'XAUUSD',
            'entry_price' => '2000',
            'tp1' => '2010',
            'stop_loss' => '1990',
        ])->assertUnauthorized();
    }

    public function test_creates_signal_and_dispatches_broadcast_job(): void
    {
        Queue::fake();

        $response = $this->withToken('test-api-token')
            ->postJson('/api/signals', [
                'market_type' => 'forex',
                'symbol' => 'XAUUSD',
                'entry_price' => '2000',
                'tp1' => '2010',
                'tp2' => '2020',
                'stop_loss' => '1990',
                'target_audience' => 'all',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.symbol', 'XAUUSD');

        $this->assertDatabaseHas('signals', [
            'symbol' => 'XAUUSD',
            'market_type' => MarketType::Forex->value,
            'target_audience' => TargetAudience::All->value,
            'status' => SignalStatus::Pending->value,
        ]);

        Queue::assertPushed(BroadcastSignalJob::class);
    }

    public function test_creates_signal_update(): void
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

        $this->withToken('test-api-token')
            ->postJson('/api/signals/update', [
                'signal_id' => $signal->id,
                'update_message_fa' => 'استاپ را به ورود منتقل کنید',
                'update_message_en' => 'Move SL to entry',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('signal_updates', [
            'signal_id' => $signal->id,
            'update_message_en' => 'Move SL to entry',
        ]);

        Queue::assertPushed(BroadcastSignalUpdateJob::class);
    }

    public function test_updates_signal_result(): void
    {
        Queue::fake();

        $signal = Signal::query()->create([
            'market_type' => MarketType::Forex,
            'symbol' => 'EURUSD',
            'entry_price' => '1.1000',
            'tp1' => '1.1050',
            'stop_loss' => '1.0950',
            'target_audience' => TargetAudience::All,
            'status' => SignalStatus::Broadcasted,
            'result' => SignalResult::Pending,
        ]);

        $this->withToken('test-api-token')
            ->postJson('/api/signals/result', [
                'signal_id' => $signal->id,
                'result' => 'win',
                'pips_gained' => 50,
            ])
            ->assertOk()
            ->assertJsonPath('data.result', 'win');

        $this->assertDatabaseHas('signals', [
            'id' => $signal->id,
            'result' => SignalResult::Win->value,
            'pips_gained' => 50,
        ]);

        Queue::assertPushed(BroadcastResultJob::class);
    }

    public function test_broadcast_signal_job_splits_into_language_queues(): void
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

        (new BroadcastSignalJob($signal))->handle();

        Queue::assertPushedOn('telegram-fa', BroadcastSignalLanguageJob::class);
        Queue::assertPushedOn('telegram-en', BroadcastSignalLanguageJob::class);

        $this->assertDatabaseHas('signals', [
            'id' => $signal->id,
            'status' => SignalStatus::Broadcasted->value,
        ]);
    }
}
