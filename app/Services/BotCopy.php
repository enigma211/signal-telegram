<?php

namespace App\Services;

use App\Enums\BotLanguage;
use App\Models\MessageTemplate;
use App\Models\TelegramUser;

class BotCopy
{
    public function get(
        string $key,
        TelegramUser|BotLanguage $userOrLang,
        array $vars = [],
        ?string $fallbackFa = null,
        ?string $fallbackEn = null
    ): string {
        $lang = $userOrLang instanceof TelegramUser
            ? $userOrLang->bot_language
            : $userOrLang;

        $template = MessageTemplate::findByKey($key);

        if ($template) {
            return $template->render($lang, $vars);
        }

        $fallback = $lang === BotLanguage::Fa
            ? ($fallbackFa ?? $fallbackEn ?? $key)
            : ($fallbackEn ?? $fallbackFa ?? $key);

        return $this->replace($fallback, $vars);
    }

    protected function replace(string $body, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $body = str_replace('{'.$key.'}', (string) $value, $body);
        }

        return preg_replace("/\n{3,}/", "\n\n", trim($body)) ?? trim($body);
    }
}
