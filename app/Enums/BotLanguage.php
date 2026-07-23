<?php

namespace App\Enums;

enum BotLanguage: string
{
    case Fa = 'fa';
    case En = 'en';

    public function label(): string
    {
        return match ($this) {
            self::Fa => 'فارسی',
            self::En => 'English',
        };
    }
}
