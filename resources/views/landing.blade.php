<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="نوا سیگنال — سیگنال‌های فارکس و کریپتو با تحلیل هوش مصنوعی">
    <title>نوا سیگنال | سیگنال هوش مصنوعی</title>
    <link rel="alternate" hreflang="fa" href="{{ url('/fa') }}">
    <link rel="alternate" hreflang="en" href="{{ url('/') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="overflow-x-hidden bg-ink-950 font-sans text-white antialiased">
    @php
        use App\Enums\BotLanguage;
        use App\Models\TelegramBot;

        $faBot = TelegramBot::findActiveByLanguage(BotLanguage::Fa);
        $enBot = TelegramBot::findActiveByLanguage(BotLanguage::En);

        $botFaUsername = $faBot?->usernameForLink()
            ?? ltrim((string) config('services.telegram.bot_username_fa'), '@');
        $botEnUsername = $enBot?->usernameForLink()
            ?? ltrim((string) config('services.telegram.bot_username_en'), '@');

        $botFa = 'https://t.me/' . $botFaUsername;
        $botEn = 'https://t.me/' . $botEnUsername;
    @endphp

    <header class="relative min-h-[100svh] overflow-hidden bg-hero-fa">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <p class="absolute -right-6 top-24 select-none font-display text-[28vw] font-extrabold leading-none text-white/[0.04] md:top-10 md:text-[18vw]">
                نُوا
            </p>
            <svg class="absolute inset-0 h-full w-full opacity-60" viewBox="0 0 1440 900" fill="none" preserveAspectRatio="xMidYMid slice">
                <path class="animate-draw-line" d="M1440 620 C1260 580 1180 500 1060 520 C900 550 840 410 680 430 C500 455 440 310 280 330 C160 345 80 270 0 250" stroke="#7DD3C7" stroke-width="2.2" stroke-dasharray="1400" stroke-linecap="round"/>
                <path class="animate-glow-line" d="M1440 700 C1240 670 1160 580 1020 595 C840 620 780 490 620 510 C440 535 380 400 220 420 C120 432 60 370 0 350" stroke="#4DB8A8" stroke-width="1.3" stroke-linecap="round"/>
            </svg>
            <div class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-ink-950 to-transparent"></div>
        </div>

        <div class="relative z-10 mx-auto flex min-h-[100svh] max-w-6xl flex-col px-6 pb-16 pt-6 md:px-10">
            <div class="flex items-center justify-between">
                <span class="sr-only">نُوا سیگنال</span>
                <a
                    href="{{ route('home') }}"
                    class="lang-switch border-white/20 bg-white/5 text-tide-300 hover:border-tide-400 hover:bg-white/10"
                    title="English"
                    aria-label="Switch to English"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M3 12h18M12 3c2.5 2.7 3.8 5.8 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.8-3.8-9S9.5 5.7 12 3z"/>
                    </svg>
                </a>
            </div>

            <div class="flex flex-1 flex-col justify-end pb-8 pt-20 md:justify-center md:pb-0 md:pt-0">
                <p class="animate-fade-up font-display text-[clamp(3rem,11vw,6.5rem)] font-extrabold leading-[1.05] text-tide-300">
                    نُوا سیگنال
                </p>

                <p class="animate-fade-up-delay mt-6 max-w-lg text-2xl font-semibold leading-relaxed text-white md:text-3xl">
                    سیگنال فارکس و کریپتو با هوش مصنوعی
                </p>

                <p class="animate-fade-up-delay mt-5 max-w-md text-base leading-8 text-white/65 md:text-lg">
                    رایگان عضو شوید؛ سیگنال‌های عمومی در تلگرام می‌آید.
                </p>

                <div class="animate-fade-up-delay-2 mt-10 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $botFa }}" target="_blank" rel="noopener noreferrer" class="btn-fa">
                        شروع با ربات فارسی
                    </a>
                    <a href="#path" class="btn-fa-ghost">
                        مسیر عضویت
                    </a>
                </div>
            </div>
        </div>
    </header>

    <section id="path" class="relative bg-tide-500 text-ink-950">
        <div class="mx-auto flex max-w-6xl flex-col md:flex-row">
            <div data-reveal class="flex-1 border-b border-ink-950/10 px-8 py-14 opacity-0 translate-y-6 transition duration-700 ease-out md:border-b-0 md:border-l md:border-ink-950/10 md:px-12 md:py-20">
                <p class="text-sm font-medium text-ink-950/45">۱</p>
                <h2 class="mt-4 text-3xl font-bold">ورود به ربات</h2>
                <p class="mt-3 text-base leading-7 text-ink-950/70">/start بزنید و عضو شوید.</p>
            </div>
            <div data-reveal class="flex-1 border-b border-ink-950/10 px-8 py-14 opacity-0 translate-y-6 transition duration-700 delay-100 ease-out md:border-b-0 md:border-l md:border-ink-950/10 md:px-12 md:py-20">
                <p class="text-sm font-medium text-ink-950/45">۲</p>
                <h2 class="mt-4 text-3xl font-bold">دریافت رایگان</h2>
                <p class="mt-3 text-base leading-7 text-ink-950/70">سیگنال‌های عمومی برای همه.</p>
            </div>
            <div data-reveal class="flex-1 px-8 py-14 opacity-0 translate-y-6 transition duration-700 delay-200 ease-out md:px-12 md:py-20">
                <p class="text-sm font-medium text-ink-950/45">۳</p>
                <h2 class="mt-4 text-3xl font-bold">ارتقای VIP</h2>
                <p class="mt-3 text-base leading-7 text-ink-950/70">هر وقت خواستید، از منوی ربات.</p>
            </div>
        </div>
    </section>

    <section class="bg-mist-50 py-24 text-ink-900 md:py-32">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div data-reveal class="opacity-0 translate-y-6 transition duration-700 ease-out">
                <h2 class="font-display text-4xl font-extrabold text-ink-900 md:text-5xl">چرا نُوا سیگنال؟</h2>
                <p class="mt-5 max-w-xl text-lg leading-8 text-ink-700">
                    موتور پایتون بازار را می‌خواند؛ ورود، اهداف و استاپ را شفاف می‌فرستد و نتیجه را ثبت می‌کند.
                </p>
            </div>

            <div class="mt-16 space-y-12">
                <article data-reveal class="opacity-0 translate-y-6 transition duration-700 delay-100 ease-out md:grid md:grid-cols-[minmax(0,12rem)_1fr] md:gap-10">
                    <div class="mb-4 h-1 w-20 bg-tide-500 md:mb-0 md:mt-3 md:h-auto md:w-1 md:self-stretch"></div>
                    <div>
                        <h3 class="text-2xl font-semibold">تحلیل هوشمند</h3>
                        <p class="mt-3 max-w-xl text-base leading-7 text-ink-700">فقط سیگنال‌های باکیفیت به تلگرام می‌رسند.</p>
                    </div>
                </article>
                <article data-reveal class="opacity-0 translate-y-6 transition duration-700 delay-200 ease-out md:grid md:grid-cols-[minmax(0,12rem)_1fr] md:gap-10">
                    <div class="mb-4 h-1 w-20 bg-ember-500 md:mb-0 md:mt-3 md:h-auto md:w-1 md:self-stretch"></div>
                    <div>
                        <h3 class="text-2xl font-semibold">آپدیت زنده</h3>
                        <p class="mt-3 max-w-xl text-base leading-7 text-ink-700">ریسک‌فری و جابه‌جایی استاپ در طول معامله.</p>
                    </div>
                </article>
                <article data-reveal class="opacity-0 translate-y-6 transition duration-700 delay-300 ease-out md:grid md:grid-cols-[minmax(0,12rem)_1fr] md:gap-10">
                    <div class="mb-4 h-1 w-20 bg-ink-800 md:mb-0 md:mt-3 md:h-auto md:w-1 md:self-stretch"></div>
                    <div>
                        <h3 class="text-2xl font-semibold">نتیجه شفاف</h3>
                        <p class="mt-3 max-w-xl text-base leading-7 text-ink-700">برد و باخت هر سیگنال ثبت می‌شود.</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="relative overflow-hidden bg-ink-900 py-24 md:py-28">
        <div class="pointer-events-none absolute left-0 top-0 h-64 w-64 -translate-x-1/3 rounded-full bg-tide-500/25 blur-3xl animate-drift" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-6xl px-6 text-center md:px-10">
            <h2 data-reveal class="font-display text-4xl font-extrabold text-tide-300 opacity-0 translate-y-6 transition duration-700 md:text-5xl">همین حالا عضو شوید</h2>
            <p data-reveal class="mx-auto mt-5 max-w-md text-base leading-8 text-white/60 opacity-0 translate-y-6 transition duration-700 delay-100">ربات فارسی را باز کنید و /start بفرستید.</p>
            <div data-reveal class="mt-10 opacity-0 translate-y-6 transition duration-700 delay-200">
                <a href="{{ $botFa }}" target="_blank" rel="noopener noreferrer" class="btn-fa">ربات فارسی</a>
            </div>
        </div>
    </section>

    <footer class="border-t border-white/10 bg-ink-950 py-10">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 md:px-10">
            <p class="font-display text-2xl font-extrabold text-tide-300">نُوا سیگنال</p>
            <a href="{{ route('home') }}" class="lang-switch border-white/20 text-tide-300 hover:border-tide-400" aria-label="English site">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M3 12h18M12 3c2.5 2.7 3.8 5.8 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.8-3.8-9S9.5 5.7 12 3z"/>
                </svg>
            </a>
        </div>
    </footer>
</body>
</html>
