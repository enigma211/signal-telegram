<?php

namespace App\Console\Commands;

use App\Services\VipSubscriptionService;
use Illuminate\Console\Command;

class RemindVipExpiryCommand extends Command
{
    protected $signature = 'vip:remind';

    protected $description = 'Notify VIP users a few days before subscription expiry';

    public function handle(VipSubscriptionService $vip): int
    {
        $count = $vip->remindExpiringSubscriptions();
        $this->info("VIP expiry reminders sent: {$count}");

        return self::SUCCESS;
    }
}
