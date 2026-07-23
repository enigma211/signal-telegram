<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Failed = 'failed';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار',
            self::Confirmed => 'تأیید شده',
            self::Failed => 'ناموفق',
            self::Paid => 'پرداخت‌شده',
        };
    }
}
