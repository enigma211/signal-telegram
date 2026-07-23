<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\TransactionChainVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyTransactionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public function __construct(public Transaction $transaction) {}

    public function backoff(): array
    {
        return [30, 60, 120, 300];
    }

    public function handle(TransactionChainVerificationService $service): void
    {
        $tx = $this->transaction->fresh();

        if (! $tx || $tx->chain_verified_at || $tx->status !== \App\Enums\TransactionStatus::Pending) {
            return;
        }

        $service->verifyOne($tx);

        $fresh = $tx->fresh();
        if ($fresh && ! $fresh->chain_verified_at && $fresh->status === \App\Enums\TransactionStatus::Pending) {
            $this->release($this->backoff()[min($this->attempts() - 1, 3)] ?? 120);
        }
    }
}
