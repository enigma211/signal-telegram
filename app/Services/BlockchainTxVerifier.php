<?php

namespace App\Services;

use App\Models\PaymentSetting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BlockchainTxVerifier
{
    public const USDT_TRC20 = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';

    public const USDT_BEP20 = '0x55d398326f99059ff775485246999027b3197955';

    /**
     * @return array{ok: bool, amount: ?float, to: ?string, message: string}
     */
    public function verify(Transaction $transaction): array
    {
        $hash = trim((string) $transaction->tx_hash);
        $network = strtoupper((string) $transaction->crypto_network);
        $expectedWallet = PaymentSetting::current()->walletForNetwork($network);
        $expectedAmount = (float) $transaction->amount;

        if ($hash === '' || blank($expectedWallet)) {
            return $this->fail('Missing tx hash or destination wallet.');
        }

        try {
            return match ($network) {
                'TRC20' => $this->verifyTrc20($hash, $expectedWallet, $expectedAmount),
                'BEP20' => $this->verifyBep20($hash, $expectedWallet, $expectedAmount),
                default => $this->fail("Unsupported network: {$network}"),
            };
        } catch (\Throwable $e) {
            Log::warning('Chain verification failed', [
                'tx' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return $this->fail($e->getMessage());
        }
    }

    /**
     * @return array{ok: bool, amount: ?float, to: ?string, message: string}
     */
    protected function verifyTrc20(string $hash, string $expectedWallet, float $expectedAmount): array
    {
        $headers = [];
        $apiKey = config('services.blockchain.trongrid_api_key');
        if (filled($apiKey)) {
            $headers['TRON-PRO-API-KEY'] = $apiKey;
        }

        $response = Http::timeout(20)
            ->withHeaders($headers)
            ->get('https://api.trongrid.io/v1/transactions/'.$hash.'/events');

        if (! $response->successful()) {
            // Fallback: TronScan public info
            $info = Http::timeout(20)
                ->get('https://apilist.tronscanapi.com/api/transaction-info', ['hash' => $hash]);

            if (! $info->successful()) {
                throw new RuntimeException('Tron API request failed.');
            }

            $json = $info->json();
            $ok = data_get($json, 'contractRet') === 'SUCCESS'
                || data_get($json, 'confirmed') === true;

            if (! $ok) {
                return $this->fail('TRC20 transaction not successful/confirmed.');
            }

            $transfers = data_get($json, 'trc20TransferInfo', []) ?: data_get($json, 'tokenTransferInfo', []);
            if (is_array($transfers) && isset($transfers['to_address'])) {
                $transfers = [$transfers];
            }

            foreach ((array) $transfers as $transfer) {
                $to = (string) (data_get($transfer, 'to_address') ?? data_get($transfer, 'toAddress') ?? '');
                $contract = (string) (data_get($transfer, 'contract_address') ?? data_get($transfer, 'contractAddress') ?? '');
                $decimals = (int) (data_get($transfer, 'decimals') ?? 6);
                $raw = data_get($transfer, 'amount_str') ?? data_get($transfer, 'amount') ?? data_get($transfer, 'quant');
                if ($raw === null) {
                    continue;
                }
                $amount = is_numeric($raw) && (float) $raw > 1000
                    ? ((float) $raw) / (10 ** $decimals)
                    : (float) $raw;

                if ($this->addressesMatch($to, $expectedWallet) && $this->amountMatches($amount, $expectedAmount)) {
                    if ($contract !== '' && ! $this->addressesMatch($contract, self::USDT_TRC20)) {
                        continue;
                    }

                    return $this->ok($amount, $to, 'TRC20 USDT transfer verified via TronScan.');
                }
            }

            $to = (string) data_get($json, 'toAddress', '');
            if ($this->addressesMatch($to, $expectedWallet)) {
                return $this->ok(null, $to, 'TRC20 tx confirmed to wallet (token amount not parsed). Enable careful manual review.');
            }

            return $this->fail('TRC20 transfer to expected wallet/amount not found.');
        }

        foreach ((array) $response->json('data') as $event) {
            if (strcasecmp((string) data_get($event, 'event_name'), 'Transfer') !== 0) {
                continue;
            }

            $contract = (string) data_get($event, 'contract_address', '');
            if ($contract !== '' && ! $this->addressesMatch($contract, self::USDT_TRC20)) {
                continue;
            }

            $result = (array) data_get($event, 'result', []);
            $to = (string) ($result['to'] ?? $result['1'] ?? '');
            $raw = $result['value'] ?? $result['0'] ?? null;
            if ($raw === null) {
                continue;
            }

            $amount = ((float) $raw) / 1_000_000;

            if ($this->addressesMatch($to, $expectedWallet) && $this->amountMatches($amount, $expectedAmount)) {
                return $this->ok($amount, $to, 'TRC20 USDT transfer verified via TronGrid.');
            }
        }

        return $this->fail('TRC20 USDT Transfer event to expected wallet not found.');
    }

    /**
     * @return array{ok: bool, amount: ?float, to: ?string, message: string}
     */
    protected function verifyBep20(string $hash, string $expectedWallet, float $expectedAmount): array
    {
        $apiKey = config('services.blockchain.bscscan_api_key');
        if (blank($apiKey)) {
            throw new RuntimeException('BSCSCAN_API_KEY is not configured.');
        }

        $receipt = Http::timeout(20)->get('https://api.bscscan.com/api', [
            'module' => 'proxy',
            'action' => 'eth_getTransactionReceipt',
            'txhash' => $hash,
            'apikey' => $apiKey,
        ])->json('result');

        if (! is_array($receipt) || strtolower((string) ($receipt['status'] ?? '')) !== '0x1') {
            return $this->fail('BEP20 transaction receipt missing or failed.');
        }

        $transferTopic = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';
        $expectedTopic = $this->addressToTopic($expectedWallet);
        $usdt = strtolower(self::USDT_BEP20);

        foreach ((array) ($receipt['logs'] ?? []) as $log) {
            $topics = $log['topics'] ?? [];
            if (($topics[0] ?? '') !== $transferTopic || count($topics) < 3) {
                continue;
            }

            if (strtolower((string) ($log['address'] ?? '')) !== $usdt) {
                continue;
            }

            if (strtolower((string) $topics[2]) !== $expectedTopic) {
                continue;
            }

            $amount = $this->tokenAmountFromHex((string) ($log['data'] ?? '0x0'), 18);

            if ($this->amountMatches($amount, $expectedAmount)) {
                return $this->ok($amount, $expectedWallet, 'BEP20 USDT transfer verified via BscScan.');
            }
        }

        return $this->fail('BEP20 USDT Transfer to expected wallet/amount not found.');
    }

    protected function tokenAmountFromHex(string $hex, int $decimals): float
    {
        $hex = strtolower(ltrim($hex, '0x'));
        if ($hex === '' || ! ctype_xdigit($hex)) {
            return 0.0;
        }

        if (function_exists('gmp_init') && function_exists('bcdiv')) {
            $wei = gmp_strval(gmp_init($hex, 16), 10);

            return (float) bcdiv($wei, bcpow('10', (string) $decimals, 0), 8);
        }

        // Fallback for small values only
        return ((float) hexdec('0x'.$hex)) / (10 ** $decimals);
    }

    protected function addressesMatch(string $a, string $b): bool
    {
        $normalize = function (string $addr): string {
            $addr = trim($addr);
            if (str_starts_with(strtolower($addr), '0x')) {
                return strtolower($addr);
            }

            return strtoupper($addr);
        };

        return $normalize($a) === $normalize($b);
    }

    protected function amountMatches(float $actual, float $expected): bool
    {
        $tolerance = (float) config('services.blockchain.amount_tolerance', 0.01);

        return abs($actual - $expected) <= max($tolerance, $expected * 0.001);
    }

    protected function addressToTopic(string $address): string
    {
        $hex = strtolower(ltrim($address, '0x'));

        return '0x'.str_pad($hex, 64, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{ok: bool, amount: ?float, to: ?string, message: string}
     */
    protected function ok(?float $amount, ?string $to, string $message): array
    {
        return ['ok' => true, 'amount' => $amount, 'to' => $to, 'message' => $message];
    }

    /**
     * @return array{ok: bool, amount: ?float, to: ?string, message: string}
     */
    protected function fail(string $message): array
    {
        return ['ok' => false, 'amount' => null, 'to' => null, 'message' => $message];
    }
}
