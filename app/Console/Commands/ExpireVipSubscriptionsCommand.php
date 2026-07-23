<?php

namespace App\Console\Commands;

use App\Services\VipSubscriptionService;
use Illuminate\Console\Command;

class ExpireVipSubscriptionsCommand extends Command
{
    protected $signature = 'vip:expire {--silent : Do not notify users}';

    protected $description = 'Downgrade expired VIP telegram users to free tier (daily cron)';

    public function handle(VipSubscriptionService $vip): int
    {
        $count = $vip->expireOverdueSubscriptions(notify: ! $this->option('silent'));
        $this->info("Expired VIP subscriptions: {$count}");

        return self::SUCCESS;
    }
}
