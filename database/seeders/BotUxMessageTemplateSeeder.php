<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

class BotUxMessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'welcome',
                'name' => 'خوش‌آمدگویی / داشبورد',
                'placeholders_help' => '',
                'body_fa' => "به «نُوا سیگنال» خوش آمدید 👋\n\nما با کمک هوش مصنوعی، بازارهای فارکس و ارز دیجیتال را تحلیل می‌کنیم و هر ساعت سیگنال‌های رایگان را برای شما ارسال می‌کنیم.\n\nاز منوی زیر گزینه موردنظرتان را انتخاب کنید.\nبا عضویت VIP به سیگنال‌های بیشتری دسترسی دارید.",
                'body_en' => "Welcome to Nova Signal 👋\n\nWe analyze Forex and Crypto markets with AI and send free signals to you every hour.\n\nChoose an option from the menu below.\nWith a VIP membership, you get access to more signals.",
            ],
            [
                'key' => 'user_blocked',
                'name' => 'کاربر مسدود شده',
                'placeholders_help' => '',
                'body_fa' => '⛔ دسترسی شما به این ربات مسدود شده است.',
                'body_en' => '⛔ Your access to this bot has been blocked.',
            ],
            [
                'key' => 'status',
                'name' => 'وضعیت اشتراک',
                'placeholders_help' => '{tier} {state} {expiry} {referral_code}',
                'body_fa' => "📊 *وضعیت اشتراک*\nسطح: *{tier}*\nوضعیت: *{state}*\nانقضا: `{expiry}`\nکد معرف: `{referral_code}`\n\nاز منوی زیر می‌توانید به داشبورد برگردید یا خرید VIP را شروع کنید.",
                'body_en' => "📊 *Subscription Status*\nPlan: *{tier}*\nStatus: *{state}*\nExpires: `{expiry}`\nReferral code: `{referral_code}`\n\nUse the menu below to return to Dashboard or buy VIP.",
            ],
            [
                'key' => 'choose_menu',
                'name' => 'انتخاب از منو',
                'placeholders_help' => '',
                'body_fa' => '🏠 *داشبورد*\nاز منوی زیر یک گزینه را انتخاب کنید.',
                'body_en' => '🏠 *Dashboard*\nPlease choose an option from the menu.',
            ],
            [
                'key' => 'support_start',
                'name' => 'شروع پشتیبانی',
                'placeholders_help' => '',
                'body_fa' => "🆘 *پشتیبانی*\nپیام خود را همین‌جا بنویسید.\nبرای خروج: /cancel",
                'body_en' => "🆘 *Support*\nWrite your message here.\nTo exit: /cancel",
            ],
            [
                'key' => 'support_cancel',
                'name' => 'خروج از پشتیبانی',
                'placeholders_help' => '',
                'body_fa' => 'خروج از پشتیبانی.',
                'body_en' => 'Left support mode.',
            ],
            [
                'key' => 'support_short',
                'name' => 'پیام پشتیبانی کوتاه',
                'placeholders_help' => '',
                'body_fa' => 'پیام خیلی کوتاه است.',
                'body_en' => 'Message is too short.',
            ],
            [
                'key' => 'support_ack',
                'name' => 'ثبت پیام پشتیبانی',
                'placeholders_help' => '{ticket_id}',
                'body_fa' => "✅ پیام شما ثبت شد (تیکت #{ticket_id}).\nمی‌توانید پیام بعدی را هم بفرستید یا /cancel بزنید.",
                'body_en' => "✅ Message received (ticket #{ticket_id}).\nYou can send another message or /cancel.",
            ],
            [
                'key' => 'support_admin_reply',
                'name' => 'پاسخ ادمین پشتیبانی',
                'placeholders_help' => '{body}',
                'body_fa' => "💬 *پاسخ پشتیبانی:*\n{body}",
                'body_en' => "💬 *Support reply:*\n{body}",
            ],
            [
                'key' => 'support_closed',
                'name' => 'بستن تیکت پشتیبانی',
                'placeholders_help' => '',
                'body_fa' => '✅ تیکت پشتیبانی بسته شد. برای پیام جدید دوباره «پشتیبانی» را بزنید.',
                'body_en' => '✅ Support ticket closed. Tap Support again to start a new message.',
            ],
            [
                'key' => 'buy_plans',
                'name' => 'انتخاب پلن VIP',
                'placeholders_help' => '{days}',
                'body_fa' => "💎 *انتخاب پلن VIP*\nمدت اشتراک: *{days}* روز\n\nیکی از پلن‌ها را انتخاب کنید:",
                'body_en' => "💎 *Choose a VIP plan*\nDuration: *{days}* days\n\nSelect one:",
            ],
            [
                'key' => 'buy_promo_ask',
                'name' => 'درخواست کد تخفیف',
                'placeholders_help' => '',
                'body_fa' => "اگر کد تخفیف دارید همین‌جا بفرستید.\nاگر ندارید روی «ادامه بدون کد» بزنید یا `/skip` بفرستید.",
                'body_en' => "Send a promo code if you have one.\nOr tap Skip / send `/skip`.",
            ],
            [
                'key' => 'buy_promo_invalid',
                'name' => 'کد تخفیف نامعتبر',
                'placeholders_help' => '',
                'body_fa' => 'کد تخفیف نامعتبر است. دوباره بفرستید یا /skip بزنید.',
                'body_en' => 'Invalid promo code. Try again or send /skip.',
            ],
            [
                'key' => 'buy_amount_network',
                'name' => 'مبلغ و انتخاب شبکه',
                'placeholders_help' => '{amount} {currency} {discount_line}',
                'body_fa' => "مبلغ قابل پرداخت: *{amount}* {currency}{discount_line}\n\nشبکه واریز را انتخاب کنید:",
                'body_en' => "Amount due: *{amount}* {currency}{discount_line}\n\nChoose payment network:",
            ],
            [
                'key' => 'buy_wallet_missing',
                'name' => 'ولت تنظیم‌نشده',
                'placeholders_help' => '{network}',
                'body_fa' => 'آدرس ولت {network} هنوز در پنل تنظیم نشده است. به پشتیبانی پیام دهید.',
                'body_en' => '{network} wallet is not configured yet. Please contact support.',
            ],
            [
                'key' => 'buy_payment_instructions',
                'name' => 'دستورالعمل پرداخت',
                'placeholders_help' => '{amount} {currency} {network} {wallet}',
                'body_fa' => "💸 *پرداخت VIP*\n\nمبلغ: `{amount}` {currency}\nشبکه: *{network}*\nآدرس ولت:\n`{wallet}`\n\nپس از واریز، *هش تراکنش (TxID)* را همین‌جا ارسال کنید.",
                'body_en' => "💸 *VIP Payment*\n\nAmount: `{amount}` {currency}\nNetwork: *{network}*\nWallet:\n`{wallet}`\n\nAfter payment, send the *transaction hash (TxID)* here.",
            ],
            [
                'key' => 'buy_invalid_hash',
                'name' => 'هش نامعتبر',
                'placeholders_help' => '',
                'body_fa' => 'هش تراکنش معتبر نیست. TxID را دوباره بفرستید.',
                'body_en' => 'Invalid transaction hash. Please resend the TxID.',
            ],
            [
                'key' => 'buy_pending_submitted',
                'name' => 'ثبت پرداخت در انتظار',
                'placeholders_help' => '{transaction_id}',
                'body_fa' => "✅ پرداخت شما ثبت شد (شماره `#{transaction_id}`).\nدر حال بررسی خودکار زنجیره / تأیید ادمین هستیم.",
                'body_en' => "✅ Payment submitted (ID `#{transaction_id}`).\nOn-chain checks / admin confirmation in progress.",
            ],
            [
                'key' => 'wallet_ask',
                'name' => 'درخواست ولت پاداش',
                'placeholders_help' => '{current}',
                'body_fa' => "👛 ولت فعلی شما برای دریافت پاداش معرفی:\n{current}\n\nآدرس ولت جدید را ارسال کنید:",
                'body_en' => "👛 Your payout wallet:\n{current}\n\nSend your new wallet address:",
            ],
            [
                'key' => 'wallet_saved',
                'name' => 'ذخیره ولت',
                'placeholders_help' => '',
                'body_fa' => '✅ ولت شما ذخیره شد.',
                'body_en' => '✅ Wallet saved.',
            ],
            [
                'key' => 'wallet_invalid',
                'name' => 'ولت نامعتبر',
                'placeholders_help' => '',
                'body_fa' => 'آدرس ولت معتبر نیست.',
                'body_en' => 'Invalid wallet address.',
            ],
            [
                'key' => 'referral',
                'name' => 'معرفی دوستان',
                'placeholders_help' => '{code} {link} {count} {percent}',
                'body_fa' => "🔗 *سیستم معرفی*\nکد شما: `{code}`\nلینک دعوت:\n`{link}`\nتعداد دعوت‌شده‌ها: *{count}*\nپاداش: *{percent}%* از خرید موفق زیرمجموعه‌ها",
                'body_en' => "🔗 *Referral*\nYour code: `{code}`\nInvite link:\n`{link}`\nReferrals: *{count}*\nReward: *{percent}%* of successful purchases",
            ],
            [
                'key' => 'help',
                'name' => 'راهنما',
                'placeholders_help' => '',
                'body_fa' => "❓ *راهنما*\nهمه کاربران به‌صورت رایگان عضو هستند و سیگنال‌های عمومی را دریافت می‌کنند.\nبا خرید VIP به سیگنال‌های بیشتر دسترسی دارید.\n\n/buy — خرید VIP\n/status — وضعیت اشتراک\n/wallet — ثبت ولت پاداش\n/referral — کد معرف\n/support — پشتیبانی\n/help — راهنما\n\nپس از واریز USDT، TxID را برای ربات بفرستید.",
                'body_en' => "❓ *Help*\nEveryone starts on the free plan and receives public/promo signals.\nVIP unlocks more signals.\n\n/buy — Buy VIP\n/status — Subscription status\n/wallet — Save payout wallet\n/referral — Referral code\n/support — Support\n/help — Help\n\nAfter sending USDT, submit the TxID.",
            ],
            [
                'key' => 'vip_expiry_reminder',
                'name' => 'یادآوری انقضای VIP',
                'placeholders_help' => '{expiry}',
                'body_fa' => "⚠️ اشتراک VIP شما تا *{expiry}* منقضی می‌شود.\nبرای تمدید از منوی خرید VIP استفاده کنید.",
                'body_en' => "⚠️ Your VIP expires on *{expiry}*.\nUse Buy VIP to renew.",
            ],
            [
                'key' => 'vip_expired',
                'name' => 'انقضای VIP',
                'placeholders_help' => '',
                'body_fa' => "⏱ اشتراک VIP شما منقضی شد.\nاکنون در حالت رایگان هستید و فقط سیگنال‌های عمومی را دریافت می‌کنید.\nبرای تمدید از منوی خرید VIP استفاده کنید.",
                'body_en' => "⏱ Your VIP subscription has expired.\nYou are now on the free plan and only receive public signals.\nUse Buy VIP to renew.",
            ],
            [
                'key' => 'payment_confirmed',
                'name' => 'تأیید پرداخت VIP',
                'placeholders_help' => '{plan} {days}',
                'body_fa' => "✅ پرداخت تأیید شد.\nپلن *{plan}* برای {days} روز فعال شد.",
                'body_en' => "✅ Payment confirmed.\n*{plan}* is active for {days} days.",
            ],
            [
                'key' => 'payment_rejected',
                'name' => 'رد پرداخت',
                'placeholders_help' => '{reason}',
                'body_fa' => "❌ پرداخت رد شد.\nدلیل: {reason}",
                'body_en' => "❌ Payment rejected.\nReason: {reason}",
            ],
            [
                'key' => 'referral_paid',
                'name' => 'پرداخت پاداش معرفی',
                'placeholders_help' => '{amount}',
                'body_fa' => '✅ پاداش معرفی *{amount}* به ولت شما واریز شد.',
                'body_en' => '✅ Referral reward *{amount}* has been paid to your wallet.',
            ],
            [
                'key' => 'tx_hash_duplicate',
                'name' => 'هش تکراری',
                'placeholders_help' => '',
                'body_fa' => 'این هش قبلاً ثبت شده است.',
                'body_en' => 'This transaction hash was already submitted.',
            ],
        ];

        foreach ($templates as $template) {
            MessageTemplate::query()->updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}
