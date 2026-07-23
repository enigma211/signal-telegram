<?php

namespace App\Console\Commands;

use App\Services\TransactionChainVerificationService;
use Illuminate\Console\Command;

class VerifyPendingTransactionsCommand extends Command
{
    protected $signature = 'payments:verify-chain {--limit=30}';

    protected $description = 'Verify pending VIP payments on-chain (TRC20/BEP20 USDT)';

    public function handle(TransactionChainVerificationService $service): int
    {
        $count = $service->verifyPending((int) $this->option('limit'));
        $this->info("Checked pending transactions: {$count}");

        return self::SUCCESS;
    }
}
