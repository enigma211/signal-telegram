<?php

namespace App\Services;

use App\Enums\BotLanguage;
use App\Enums\SubscriptionTier;
use App\Models\TelegramUser;
use InvalidArgumentException;
use Throwable;

class VipBotHandler
{
    public function __construct(
        protected VipSubscriptionService $vip,
        protected TelegramService $telegram,
        protected BotCopy $copy
    ) {}

    public function handleCallback(TelegramUser $user, string $chatId, string $callbackId, string $data): void
    {
        $telegram = $this->telegram->forUser($user);

        match (true) {
            $data === 'menu:buy' => $this->onBuyMenu($user, $chatId, $callbackId, $telegram),
            $data === 'menu:status' => $this->onStatus($user, $chatId, $callbackId, $telegram),
            $data === 'menu:wallet' => $this->onWalletPrompt($user, $chatId, $callbackId, $telegram),
            $data === 'menu:ref' => $this->onReferral($user, $chatId, $callbackId, $telegram),
            $data === 'menu:help' => $this->onHelp($user, $chatId, $callbackId, $telegram),
            $data === 'menu:support' => $this->onSupport($user, $chatId, $callbackId, $telegram),
            $data === 'support:cancel' => $this->onSupportCancel($user, $chatId, $callbackId, $telegram),
            $data === 'promo:skip' => $this->onPromoSkip($user, $chatId, $callbackId, $telegram),
            str_starts_with($data, 'plan:') => $this->onPlanSelected($user, $chatId, $callbackId, $data, $telegram),
            str_starts_with($data, 'net:') => $this->onNetworkSelected($user, $chatId, $callbackId, $data, $telegram),
            default => $telegram->answerCallbackQuery($callbackId),
        };
    }

    public function handleText(TelegramUser $user, string $chatId, string $text): void
    {
        $telegram = $this->telegram->forUser($user);
        $normalized = mb_strtolower(trim($text));

        if (str_starts_with($normalized, '/start')) {
            return;
        }

        if (in_array($normalized, ['/buy', 'خرید', 'buy', '/vip'], true)) {
            $this->showPlans($user, $chatId, $telegram);

            return;
        }

        if (in_array($normalized, ['/status', 'وضعیت', 'status'], true)) {
            $telegram->sendMessage($chatId, $this->vip->statusText($user), $this->menuKeyboard($user));

            return;
        }

        if (in_array($normalized, ['/wallet', 'ولت', 'wallet'], true)) {
            $this->askWallet($user, $chatId, $telegram);

            return;
        }

        if (in_array($normalized, ['/referral', 'معرف', 'referral'], true)) {
            $telegram->sendMessage($chatId, $this->referralText($user), $this->menuKeyboard($user));

            return;
        }

        if (in_array($normalized, ['/help', 'راهنما', 'help', '/menu', 'منو'], true)) {
            $telegram->sendMessage($chatId, $this->helpText($user), $this->menuKeyboard($user));

            return;
        }

        if (in_array($normalized, ['/support', 'پشتیبانی', 'support'], true)) {
            $this->startSupport($user, $chatId, $telegram);

            return;
        }

        if (in_array($normalized, ['/cancel', 'cancel', 'انصراف'], true) && $user->bot_state === 'await_support') {
            app(SupportService::class)->endSession($user);
            $telegram->sendMessage(
                $chatId,
                $this->copy->get('support_cancel', $user, [], 'خروج از پشتیبانی.', 'Left support mode.'),
                $this->menuKeyboard($user)
            );

            return;
        }

        if (in_array($normalized, ['/skip', 'skip'], true) && $user->bot_state === 'await_promo') {
            $this->afterPromo($user, $chatId, null, $telegram);

            return;
        }

        match ($user->bot_state) {
            'await_promo' => $this->receivePromo($user, $chatId, $text, $telegram),
            'await_tx_hash' => $this->receiveTxHash($user, $chatId, $text, $telegram),
            'await_wallet' => $this->receiveWallet($user, $chatId, $text, $telegram),
            'await_support' => $this->receiveSupportMessage($user, $chatId, $text, $telegram),
            default => $telegram->sendMessage(
                $chatId,
                $this->copy->get('choose_menu', $user, [], 'از منوی زیر یک گزینه را انتخاب کنید.', 'Please choose an option from the menu.'),
                $this->menuKeyboard($user)
            ),
        };
    }

    public function menuKeyboard(TelegramUser $user): array
    {
        $fa = $user->bot_language === BotLanguage::Fa;

        $rows = [
            [
                ['text' => $fa ? '💎 خرید VIP' : '💎 Buy VIP', 'callback_data' => 'menu:buy'],
                ['text' => $fa ? '📊 وضعیت' : '📊 Status', 'callback_data' => 'menu:status'],
            ],
            [
                ['text' => $fa ? '👛 ولت من' : '👛 My Wallet', 'callback_data' => 'menu:wallet'],
                ['text' => $fa ? '🔗 معرفی' : '🔗 Referral', 'callback_data' => 'menu:ref'],
            ],
            [
                ['text' => $fa ? '🆘 پشتیبانی' : '🆘 Support', 'callback_data' => 'menu:support'],
                ['text' => $fa ? '❓ راهنما' : '❓ Help', 'callback_data' => 'menu:help'],
            ],
        ];

        return ['inline_keyboard' => $rows];
    }

    protected function onSupport(TelegramUser $user, string $chatId, string $callbackId, TelegramService $telegram): void
    {
        $telegram->answerCallbackQuery($callbackId);
        $this->startSupport($user, $chatId, $telegram);
    }

    protected function onSupportCancel(TelegramUser $user, string $chatId, string $callbackId, TelegramService $telegram): void
    {
        $telegram->answerCallbackQuery($callbackId);
        app(SupportService::class)->endSession($user);
        $telegram->sendMessage(
            $chatId,
            $this->copy->get('support_cancel', $user, [], 'خروج از پشتیبانی.', 'Left support mode.'),
            $this->menuKeyboard($user)
        );
    }

    protected function startSupport(TelegramUser $user, string $chatId, TelegramService $telegram): void
    {
        app(SupportService::class)->startSession($user);

        $fa = $user->bot_language === BotLanguage::Fa;
        $text = $this->copy->get(
            'support_start',
            $user,
            [],
            "🆘 *پشتیبانی*\nپیام خود را همین‌جا بنویسید.\nبرای خروج: /cancel",
            "🆘 *Support*\nWrite your message here.\nTo exit: /cancel"
        );

        $telegram->sendMessage($chatId, $text, [
            'inline_keyboard' => [[
                ['text' => $fa ? 'انصراف' : 'Cancel', 'callback_data' => 'support:cancel'],
            ]],
        ]);
    }

    protected function receiveSupportMessage(
        TelegramUser $user,
        string $chatId,
        string $text,
        TelegramService $telegram
    ): void {
        if (mb_strlen(trim($text)) < 2) {
            $telegram->sendMessage(
                $chatId,
                $this->copy->get('support_short', $user, [], 'پیام خیلی کوتاه است.', 'Message is too short.')
            );

            return;
        }

        $message = app(SupportService::class)->addUserMessage($user, $text);

        $telegram->sendMessage(
            $chatId,
            $this->copy->get(
                'support_ack',
                $user,
                ['ticket_id' => (string) $message->support_ticket_id],
                "✅ پیام شما ثبت شد (تیکت #{$message->support_ticket_id}).\nمی‌توانید پیام بعدی را هم بفرستید یا /cancel بزنید.",
                "✅ Message received (ticket #{$message->support_ticket_id}).\nYou can send another message or /cancel."
            ),
            [
                'inline_keyboard' => [[
                    [
                        'text' => $this->t($user, 'انصراف از پشتیبانی', 'Exit support'),
                        'callback_data' => 'support:cancel',
                    ],
                ]],
            ]
        );
    }

    protected function onBuyMenu(TelegramUser $user, string $chatId, string $callbackId, TelegramService $telegram): void
    {
        $telegram->answerCallbackQuery($callbackId);
        $this->showPlans($user, $chatId, $telegram);
    }

    protected function onStatus(TelegramUser $user, string $chatId, string $callbackId, TelegramService $telegram): void
    {
        $telegram->answerCallbackQuery($callbackId);
        $telegram->sendMessage($chatId, $this->vip->statusText($user), $this->menuKeyboard($user));
    }

    protected function onWalletPrompt(TelegramUser $user, string $chatId, string $callbackId, TelegramService $telegram): void
    {
        $telegram->answerCallbackQuery($callbackId);
        $this->askWallet($user, $chatId, $telegram);
    }

    protected function onReferral(TelegramUser $user, string $chatId, string $callbackId, TelegramService $telegram): void
    {
        $telegram->answerCallbackQuery($callbackId);
        $telegram->sendMessage($chatId, $this->referralText($user), $this->menuKeyboard($user));
    }

    protected function onHelp(TelegramUser $user, string $chatId, string $callbackId, TelegramService $telegram): void
    {
        $telegram->answerCallbackQuery($callbackId);
        $telegram->sendMessage($chatId, $this->helpText($user), $this->menuKeyboard($user));
    }

    protected function showPlans(TelegramUser $user, string $chatId, TelegramService $telegram): void
    {
        $settings = $this->vip->settings();
        $fa = $user->bot_language === BotLanguage::Fa;
        $currency = $settings->currency;

        $text = $this->copy->get(
            'buy_plans',
            $user,
            ['days' => (string) $settings->subscription_days],
            "💎 *انتخاب پلن VIP*\nمدت اشتراک: *{$settings->subscription_days}* روز\n\nیکی از پلن‌ها را انتخاب کنید:",
            "💎 *Choose a VIP plan*\nDuration: *{$settings->subscription_days}* days\n\nSelect one:"
        );

        $keyboard = [
            'inline_keyboard' => [
                [[
                    'text' => ($fa ? 'فارکس' : 'Forex')." — {$settings->price_forex} {$currency}",
                    'callback_data' => 'plan:forex',
                ]],
                [[
                    'text' => ($fa ? 'کریپتو' : 'Crypto')." — {$settings->price_crypto} {$currency}",
                    'callback_data' => 'plan:crypto',
                ]],
                [[
                    'text' => ($fa ? 'کامل (فارکس+کریپتو)' : 'Full (Forex+Crypto)')." — {$settings->price_full} {$currency}",
                    'callback_data' => 'plan:full',
                ]],
            ],
        ];

        $telegram->sendMessage($chatId, $text, $keyboard);
    }

    protected function onPlanSelected(
        TelegramUser $user,
        string $chatId,
        string $callbackId,
        string $data,
        TelegramService $telegram
    ): void {
        $tier = $this->tierFromCallback($data);
        $telegram->answerCallbackQuery($callbackId);

        $user->update([
            'bot_state' => 'await_promo',
            'bot_state_payload' => [
                'tier' => $tier->value,
            ],
        ]);

        $fa = $user->bot_language === BotLanguage::Fa;
        $text = $this->copy->get(
            'buy_promo_ask',
            $user,
            [],
            "اگر کد تخفیف دارید همین‌جا بفرستید.\nاگر ندارید روی «ادامه بدون کد» بزنید یا `/skip` بفرستید.",
            "Send a promo code if you have one.\nOr tap Skip / send `/skip`."
        );

        $keyboard = [
            'inline_keyboard' => [[
                ['text' => $fa ? 'ادامه بدون کد' : 'Skip promo', 'callback_data' => 'promo:skip'],
            ]],
        ];

        $telegram->sendMessage($chatId, $text, $keyboard);
    }

    protected function onPromoSkip(
        TelegramUser $user,
        string $chatId,
        string $callbackId,
        TelegramService $telegram
    ): void {
        $telegram->answerCallbackQuery($callbackId);
        $this->afterPromo($user, $chatId, null, $telegram);
    }

    protected function receivePromo(
        TelegramUser $user,
        string $chatId,
        string $text,
        TelegramService $telegram
    ): void {
        $promo = $this->vip->resolvePromo($text);

        if (! $promo) {
            $telegram->sendMessage(
                $chatId,
                $this->copy->get('buy_promo_invalid', $user, [], 'کد تخفیف نامعتبر است. دوباره بفرستید یا /skip بزنید.', 'Invalid promo code. Try again or send /skip.')
            );

            return;
        }

        $this->afterPromo($user, $chatId, $promo->code, $telegram);
    }

    protected function afterPromo(
        TelegramUser $user,
        string $chatId,
        ?string $promoCode,
        TelegramService $telegram
    ): void {
        $payload = $user->bot_state_payload ?? [];
        if (empty($payload['tier'])) {
            $telegram->sendMessage($chatId, $this->t($user, 'لطفاً دوباره پلن را انتخاب کنید.', 'Please select a plan again.'), $this->menuKeyboard($user));

            return;
        }

        $tier = SubscriptionTier::from($payload['tier']);
        $promo = $this->vip->resolvePromo($promoCode);
        $original = $this->vip->priceForTier($tier);
        $amount = $this->vip->discountedAmount($original, $promo);

        $user->update([
            'bot_state' => 'await_network',
            'bot_state_payload' => [
                'tier' => $tier->value,
                'promo_code' => $promo?->code,
                'amount' => $amount,
                'original_amount' => $original,
            ],
        ]);

        $fa = $user->bot_language === BotLanguage::Fa;
        $discountLine = $promo
            ? ($fa ? "\nتخفیف: *{$promo->discount_percentage}%*" : "\nDiscount: *{$promo->discount_percentage}%*")
            : '';

        $text = $this->copy->get(
            'buy_amount_network',
            $user,
            [
                'amount' => (string) $amount,
                'currency' => $this->vip->settings()->currency,
                'discount_line' => $discountLine,
            ],
            "مبلغ قابل پرداخت: *{$amount}* {$this->vip->settings()->currency}{$discountLine}\n\nشبکه واریز را انتخاب کنید:",
            "Amount due: *{$amount}* {$this->vip->settings()->currency}{$discountLine}\n\nChoose payment network:"
        );

        $telegram->sendMessage($chatId, $text, [
            'inline_keyboard' => [
                [
                    ['text' => 'TRC20', 'callback_data' => 'net:TRC20'],
                    ['text' => 'BEP20', 'callback_data' => 'net:BEP20'],
                ],
            ],
        ]);
    }

    protected function onNetworkSelected(
        TelegramUser $user,
        string $chatId,
        string $callbackId,
        string $data,
        TelegramService $telegram
    ): void {
        $network = strtoupper(substr($data, 4));
        $telegram->answerCallbackQuery($callbackId);

        $payload = $user->bot_state_payload ?? [];
        if (empty($payload['tier']) || empty($payload['amount'])) {
            $telegram->sendMessage($chatId, $this->t($user, 'لطفاً دوباره از خرید شروع کنید.', 'Please restart the purchase.'), $this->menuKeyboard($user));

            return;
        }

        $wallet = $this->vip->settings()->walletForNetwork($network);
        if (blank($wallet)) {
            $telegram->sendMessage(
                $chatId,
                $this->copy->get(
                    'buy_wallet_missing',
                    $user,
                    ['network' => $network],
                    "آدرس ولت {$network} هنوز در پنل تنظیم نشده است. به پشتیبانی پیام دهید.",
                    "{$network} wallet is not configured yet. Please contact support."
                ),
                $this->menuKeyboard($user)
            );

            return;
        }

        $user->update([
            'bot_state' => 'await_tx_hash',
            'bot_state_payload' => array_merge($payload, ['network' => $network]),
        ]);

        $amount = $payload['amount'];
        $currency = $this->vip->settings()->currency;

        $text = $this->copy->get(
            'buy_payment_instructions',
            $user,
            [
                'amount' => (string) $amount,
                'currency' => $currency,
                'network' => $network,
                'wallet' => $wallet,
            ],
            "💸 *پرداخت VIP*\n\nمبلغ: `{$amount}` {$currency}\nشبکه: *{$network}*\nآدرس ولت:\n`{$wallet}`\n\nپس از واریز، *هش تراکنش (TxID)* را همین‌جا ارسال کنید.",
            "💸 *VIP Payment*\n\nAmount: `{$amount}` {$currency}\nNetwork: *{$network}*\nWallet:\n`{$wallet}`\n\nAfter payment, send the *transaction hash (TxID)* here."
        );

        $telegram->sendMessage($chatId, $text);
    }

    protected function receiveTxHash(
        TelegramUser $user,
        string $chatId,
        string $text,
        TelegramService $telegram
    ): void {
        $payload = $user->bot_state_payload ?? [];
        $hash = trim($text);

        if (strlen($hash) < 10 || str_contains($hash, ' ')) {
            $telegram->sendMessage(
                $chatId,
                $this->copy->get('buy_invalid_hash', $user, [], 'هش تراکنش معتبر نیست. TxID را دوباره بفرستید.', 'Invalid transaction hash. Please resend the TxID.')
            );

            return;
        }

        try {
            $tier = SubscriptionTier::from($payload['tier']);
            $promo = $this->vip->resolvePromo($payload['promo_code'] ?? null);
            $transaction = $this->vip->createPendingSubscription(
                $user,
                $tier,
                $payload['network'] ?? 'TRC20',
                $hash,
                $promo
            );
        } catch (Throwable $e) {
            $telegram->sendMessage($chatId, $e->getMessage(), $this->menuKeyboard($user));

            return;
        }

        $text = $this->copy->get(
            'buy_pending_submitted',
            $user,
            ['transaction_id' => (string) $transaction->id],
            "✅ پرداخت شما ثبت شد (شماره `#{$transaction->id}`).\nدر حال بررسی خودکار زنجیره / تأیید ادمین هستیم.",
            "✅ Payment submitted (ID `#{$transaction->id}`).\nOn-chain checks / admin confirmation in progress."
        );

        $telegram->sendMessage($chatId, $text, $this->menuKeyboard($user->fresh()));
    }

    protected function askWallet(TelegramUser $user, string $chatId, TelegramService $telegram): void
    {
        $user->update(['bot_state' => 'await_wallet', 'bot_state_payload' => null]);

        $current = $user->crypto_wallet_address
            ? "`{$user->crypto_wallet_address}`"
            : $this->t($user, 'ثبت نشده', 'Not set');

        $text = $this->copy->get(
            'wallet_ask',
            $user,
            ['current' => $current],
            "👛 ولت فعلی شما برای دریافت پاداش معرفی:\n{$current}\n\nآدرس ولت جدید را ارسال کنید:",
            "👛 Your payout wallet:\n{$current}\n\nSend your new wallet address:"
        );

        $telegram->sendMessage($chatId, $text);
    }

    protected function receiveWallet(
        TelegramUser $user,
        string $chatId,
        string $text,
        TelegramService $telegram
    ): void {
        $wallet = trim($text);
        if (strlen($wallet) < 10) {
            $telegram->sendMessage($chatId, $this->copy->get('wallet_invalid', $user, [], 'آدرس ولت معتبر نیست.', 'Invalid wallet address.'));

            return;
        }

        $user->update([
            'crypto_wallet_address' => $wallet,
            'bot_state' => null,
            'bot_state_payload' => null,
        ]);

        $telegram->sendMessage(
            $chatId,
            $this->copy->get('wallet_saved', $user, [], '✅ ولت شما ذخیره شد.', '✅ Wallet saved.'),
            $this->menuKeyboard($user)
        );
    }

    protected function referralText(TelegramUser $user): string
    {
        $percent = $this->vip->settings()->referral_percent;
        $count = $user->referrals()->count();
        $link = $user->referralInviteUrl();

        return $this->copy->get(
            'referral',
            $user,
            [
                'code' => $user->referral_code,
                'link' => $link,
                'count' => (string) $count,
                'percent' => (string) $percent,
            ],
            "🔗 *سیستم معرفی*\nکد شما: `{$user->referral_code}`\nلینک دعوت:\n`{$link}`\nتعداد دعوت‌شده‌ها: *{$count}*\nپاداش: *{$percent}%* از خرید موفق زیرمجموعه‌ها",
            "🔗 *Referral*\nYour code: `{$user->referral_code}`\nInvite link:\n`{$link}`\nReferrals: *{$count}*\nReward: *{$percent}%* of successful purchases"
        );
    }

    protected function helpText(TelegramUser $user): string
    {
        return $this->copy->get(
            'help',
            $user,
            [],
            "❓ *راهنما*\nهمه کاربران به‌صورت رایگان عضو هستند و سیگنال‌های عمومی (نمونه تبلیغاتی) را دریافت می‌کنند.\nبا خرید VIP به سیگنال‌های بیشتر دسترسی دارید.\n\n/buy — خرید VIP\n/status — وضعیت اشتراک\n/wallet — ثبت ولت پاداش\n/referral — کد معرف\n/support — پشتیبانی\n/help — راهنما",
            "❓ *Help*\nEveryone starts on the free plan and receives public/promo signals.\nVIP unlocks more signals.\n\n/buy — Buy VIP\n/status — Subscription status\n/wallet — Save payout wallet\n/referral — Referral code\n/support — Support\n/help — Help"
        );
    }

    protected function tierFromCallback(string $data): SubscriptionTier
    {
        return match ($data) {
            'plan:forex' => SubscriptionTier::VipForex,
            'plan:crypto' => SubscriptionTier::VipCrypto,
            'plan:full' => SubscriptionTier::VipFull,
            default => throw new InvalidArgumentException('Unknown plan.'),
        };
    }

    protected function t(TelegramUser $user, string $fa, string $en): string
    {
        return $user->bot_language === BotLanguage::Fa ? $fa : $en;
    }
}
