<?php

namespace Tests\Feature;

use App\Enums\MarketType;
use App\Enums\SignalResult;
use App\Enums\SignalStatus;
use App\Enums\TargetAudience;
use App\Jobs\BroadcastResultJob;
use App\Jobs\BroadcastSignalJob;
use App\Jobs\BroadcastSignalLanguageJob;
use App\Jobs\PostResultToTwitterJob;
use App\Jobs\PostSignalToTwitterJob;
use App\Models\Signal;
use App\Models\TwitterSetting;
use App\Services\SignalMessageBuilder;
use App\Services\TwitterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TwitterBroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected function enableTwitter(array $overrides = []): TwitterSetting
    {
        return TwitterSetting::query()->create(array_merge([
            'enabled' => true,
            'api_key' => 'key',
            'api_secret' => 'secret',
            'access_token' => 'token',
            'access_token_secret' => 'token_secret',
            'post_signals' => true,
            'post_results' => true,
            'post_vip' => false,
            'cta' => null,
        ], $overrides));
    }

    public function test_broadcast_signal_dispatches_twitter_job(): void
    {
        $this->enableTwitter();
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

        Queue::assertPushed(BroadcastSignalLanguageJob::class, 2);
        Queue::assertPushed(PostSignalToTwitterJob::class);
    }

    public function test_twitter_job_skips_vip_when_disabled(): void
    {
        $this->enableTwitter(['post_vip' => false]);
        Http::fake();

        $signal = Signal::query()->create([
            'market_type' => MarketType::Forex,
            'symbol' => 'EURUSD',
            'entry_price' => '1.1',
            'tp1' => '1.2',
            'stop_loss' => '1.0',
            'target_audience' => TargetAudience::VipOnly,
            'status' => SignalStatus::Broadcasted,
            'result' => SignalResult::Pending,
        ]);

        (new PostSignalToTwitterJob($signal))->handle(
            app(TwitterService::class),
            app(SignalMessageBuilder::class),
            app(\App\Services\TwitterDeliveryLogger::class)
        );

        Http::assertNothingSent();
    }

    public function test_broadcast_result_dispatches_twitter_job(): void
    {
        $this->enableTwitter();
        Queue::fake();

        $signal = Signal::query()->create([
            'market_type' => MarketType::Crypto,
            'symbol' => 'BTCUSDT',
            'entry_price' => '60000',
            'tp1' => '61000',
            'stop_loss' => '59000',
            'target_audience' => TargetAudience::All,
            'status' => SignalStatus::Broadcasted,
            'result' => SignalResult::Win,
            'pips_gained' => 100,
        ]);

        (new BroadcastResultJob($signal))->handle();

        Queue::assertPushed(PostResultToTwitterJob::class);
    }

    public function test_twitter_service_strips_markdown_and_fits_length(): void
    {
        $twitter = app(TwitterService::class);
        $plain = $twitter->toPlainText("📡 *New AI Signal*\nEntry: `2000`\n_risk_");
        $this->assertStringNotContainsString('*', $plain);
        $this->assertStringNotContainsString('`', $plain);
        $this->assertSame(280, mb_strlen($twitter->fitTweet(str_repeat('a', 500))));
    }

    public function test_twitter_settings_managed_via_model_current(): void
    {
        $settings = TwitterSetting::current();
        $this->assertFalse($settings->enabled);

        $settings->update([
            'enabled' => true,
            'api_key' => 'k',
            'api_secret' => 's',
            'access_token' => 't',
            'access_token_secret' => 'ts',
        ]);

        $fresh = TwitterSetting::current();
        $this->assertTrue($fresh->isReady());
        $this->assertSame('k', $fresh->api_key);
    }
}
