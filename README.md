# Nova Signal — پلتفرم سیگنال تلگرام (FA/EN)

Laravel 11 + Filament 3 + دو ربات تلگرام (فارسی/انگلیسی) + API امن برای موتور پایتون.

## پیش‌نیازها

- PHP 8.2+ با extensionهای رایج Laravel (mbstring, pdo_mysql, openssl, …)
- Composer
- MySQL
- Node.js + npm (برای Vite / لندینگ)
- روی سرور: Supervisor (صف) و cron یا scheduler جدا برای `schedule:run`
- `APP_URL` عمومی با **HTTPS** برای Webhook تلگرام

## نصب سریع (محلی / Laragon)

```bash
cp .env.example .env
php artisan key:generate
```

در `.env` حداقل این‌ها را پر کنید:

| متغیر | توضیح |
|--------|--------|
| `APP_URL` | مثلاً `http://signal-telegram.test` یا دامنه HTTPS پروداکشن |
| `DB_*` | دیتابیس MySQL |
| `API_TOKEN` | توکن تصادفی برای API پایتون |
| `TELEGRAM_WEBHOOK_SECRET` | secret مشترک webhook (الزامی) |
| `QUEUE_CONNECTION` | معمولاً `database` |
| `PRICE_*_USDT` | فقط پیش‌فرض seed؛ قیمت واقعی از **تنظیمات پرداخت VIP** در پنل |

تولید secret:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

سپس:

```bash
php artisan migrate
php artisan db:seed
php artisan make:filament-user
npm install && npm run build
```

پنل ادمین: `{APP_URL}/admin`

اگر کاربر ادمین از قبل دارید و می‌خواهید دوباره بسازید:

```bash
php artisan make:filament-user
```

## تنظیم ربات و Webhook

1. در BotFather دو ربات FA و EN بسازید.
2. در پنل: **تلگرام → ربات‌های تلگرام** توکن و یوزرنیم را ثبت کنید.
3. مطمئن شوید `TELEGRAM_WEBHOOK_SECRET` در `.env` ست شده (`php artisan config:clear`).
4. روی هر ربات دکمه **تنظیم Webhook** را بزنید.

آدرس‌ها:

- `POST {APP_URL}/api/telegram/webhook/fa`
- `POST {APP_URL}/api/telegram/webhook/en`

تلگرام هنگام ارسال آپدیت هدر `X-Telegram-Bot-Api-Secret-Token` را با همان secret می‌فرستد. بدون آن درخواست‌ها `401` می‌گیرند.

کانال‌ها: ربات را ادمین کانال کنید و Chat ID را در **کانال‌های تلگرام** ثبت کنید.

## صف (Redis + Horizon)

بدون worker، سیگنال در صف می‌ماند و به تلگرام نمی‌رود.
پیشنهاد: `QUEUE_CONNECTION=redis` و Redis روشن.

### لینوکس (Horizon)

```bash
sudo bash deploy/setup-queue-linux.sh /var/www/signal-telegram www-data
```

- `deploy/supervisor/signal-telegram-horizon.conf`
- داشبورد: `{APP_URL}/horizon` (کاربر لاگین‌شده)

### ویندوز (Laragon)

Horizon به `pcntl` نیاز دارد و روی ویندوز اجرا نمی‌شود. از worker Redis استفاده کنید:

```bat
deploy\queue-work.bat
```

دو صف `telegram-fa` و `telegram-en`.

## زمان‌بندی (Scheduler)

دستورهای زمان‌بندی‌شده در `routes/console.php`:

| دستور | زمان | کار |
|--------|------|-----|
| `vip:expire` | هر روز 00:15 | قطع VIP منقضی |
| `vip:remind` | هر روز 10:00 | اعلان نزدیک‌بودن انقضا |
| `payments:verify-chain` | هر ۲ دقیقه | تأیید هش USDT روی زنجیره |

لندینگ: `/` انگلیسی (پیش‌فرض) — `/fa` فارسی.

### لینوکس (cron کلاسیک)

```cron
* * * * * cd /var/www/signal-telegram && php artisan schedule:run >> /dev/null 2>&1
```

اگر از Supervisor scheduler استفاده می‌کنید، cron جدا لازم نیست.

### ویندوز

`deploy\schedule-run.bat` را با Task Scheduler هر دقیقه اجرا کنید، یا دستی:

```bash
php artisan schedule:run
```

## API موتور پایتون

مستند کامل: [`docs/python-api.md`](docs/python-api.md)  
نمونه کلاینت: [`examples/python/signal_client.py`](examples/python/signal_client.py)

هدر یکی از این دو:

```http
Authorization: Bearer {API_TOKEN}
X-API-Token: {API_TOKEN}
```

| متد | مسیر | کار |
|------|------|-----|
| `POST` | `/api/signals` | سیگنال جدید + صف broadcast |
| `POST` | `/api/signals/update` | آپدیت سیگنال |
| `POST` | `/api/signals/result` | نتیجه (win/loss/…) |

بدون `API_TOKEN` معتبر → `401`.

## چک‌لیست پروداکشن

- [ ] `APP_ENV=production` و `APP_DEBUG=false`
- [ ] `APP_URL=https://...` عمومی
- [ ] `API_TOKEN` و `TELEGRAM_WEBHOOK_SECRET` قوی
- [ ] migrate + seed + کاربر Filament
- [ ] ربات‌ها + **تنظیم Webhook** دوباره بعد از ست‌کردن secret
- [ ] تنظیمات پرداخت VIP (ولت، قیمت، روز اشتراک)
- [ ] Redis + Horizon (لینوکس) یا `queue:work redis` (ویندوز)
- [ ] `BSCSCAN_API_KEY` برای تأیید BEP20؛ اختیاری `TRONGRID_API_KEY`
- [ ] scheduler/cron فعال
- [ ] `npm run build` و دسترسی نوشتن به `storage/` و `bootstrap/cache`

## تست

```bash
php artisan test
```

## ساختار مفید

```
app/Http/Controllers/TelegramWebhookController.php
app/Http/Controllers/Api/PythonSignalController.php
app/Http/Middleware/ValidateTelegramWebhookSecret.php
app/Jobs/BroadcastSignalLanguageJob.php
deploy/
routes/api.php
routes/console.php
```
