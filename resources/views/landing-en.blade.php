<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Nova Signal — Forex & crypto signals powered by AI">
    <title>Nova Signal | AI Trading Signals</title>
    <link rel="alternate" hreflang="en" href="{{ url('/') }}">
    <link rel="alternate" hreflang="fa" href="{{ url('/fa') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="overflow-x-hidden bg-mist-50 font-enSans text-ink-900 antialiased">
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

    <header class="relative min-h-[100svh] overflow-hidden bg-hero-en">
        {{-- Full-bleed market visual --}}
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <svg class="absolute inset-0 h-full w-full opacity-70" viewBox="0 0 1440 900" fill="none" preserveAspectRatio="xMidYMid slice">
                <path class="animate-draw-line" d="M0 640 C160 600 240 520 360 540 C520 570 580 430 740 450 C920 475 980 330 1140 350 C1260 365 1340 290 1440 270" stroke="#2A9B8A" stroke-width="2.2" stroke-dasharray="1400" stroke-linecap="round"/>
                <path class="animate-glow-line" d="M0 700 C200 670 280 580 420 595 C600 620 660 490 820 510 C1000 535 1060 400 1220 420 C1320 432 1380 370 1440 350" stroke="#E07A3D" stroke-width="1.4" stroke-opacity="0.55" stroke-linecap="round"/>
                <g fill="#1F7A6C" fill-opacity="0.2">
                    <rect x="220" y="520" width="12" height="90" rx="1"/>
                    <rect x="248" y="480" width="12" height="130" rx="1"/>
                    <rect x="560" y="440" width="12" height="100" rx="1"/>
                    <rect x="588" y="400" width="12" height="140" rx="1"/>
                    <rect x="920" y="360" width="12" height="95" rx="1"/>
                    <rect x="948" y="320" width="12" height="135" rx="1"/>
                </g>
            </svg>
            <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-mist-50 to-transparent"></div>
        </div>

        <div class="relative z-10 mx-auto flex min-h-[100svh] max-w-6xl flex-col px-6 pb-16 pt-6 md:px-10">
            <div class="flex items-center justify-between">
                <span class="sr-only">Nova Signal</span>
                <a
                    href="{{ route('home.fa') }}"
                    class="lang-switch ml-auto border-ink-900/15 bg-white/40 text-ink-800 backdrop-blur-sm hover:border-tide-500 hover:text-tide-600"
                    title="فارسی"
                    aria-label="Switch to Persian"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M3 12h18M12 3c2.5 2.7 3.8 5.8 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.8-3.8-9S9.5 5.7 12 3z"/>
                    </svg>
                </a>
            </div>

            <div class="flex flex-1 flex-col justify-center pt-10 md:pt-0">
                <p class="animate-fade-up font-enDisplay text-[clamp(3.5rem,12vw,8rem)] font-semibold leading-[0.9] tracking-tight text-sea-950">
                    Nova<br><span class="text-tide-600">Signal</span>
                </p>

                <p class="animate-fade-up-delay mt-8 max-w-md text-lg leading-8 text-ink-800/75 md:text-xl">
                    AI forex & crypto setups, delivered to Telegram.
                </p>

                <div class="animate-fade-up-delay-2 mt-10 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a href="{{ $botEn }}" target="_blank" rel="noopener noreferrer" class="btn-en">
                        Open English Bot
                    </a>
                    <a href="#how" class="btn-en-ghost">
                        How it works
                    </a>
                </div>
            </div>
        </div>
    </header>

    <section class="relative overflow-hidden bg-sea-950 py-24 text-white md:py-32">
        <div class="pointer-events-none absolute -left-24 top-10 h-72 w-72 rounded-full bg-tide-500/20 blur-3xl animate-drift" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-6xl px-6 md:px-10">
            <div data-reveal class="opacity-0 translate-y-6 transition duration-700 ease-out">
                <h2 class="font-enDisplay text-4xl font-semibold tracking-tight md:text-5xl">Clarity over noise</h2>
                <p class="mt-5 max-w-xl text-lg leading-8 text-white/65">
                    Entry, targets, and stop — then live updates and logged outcomes.
                </p>
            </div>

            <div class="mt-16 grid gap-12 border-t border-white/10 pt-12 md:grid-cols-3 md:gap-10">
                <div data-reveal class="opacity-0 translate-y-6 transition duration-700 delay-100 ease-out">
                    <p class="font-enDisplay text-5xl font-semibold text-tide-300">01</p>
                    <h3 class="mt-4 text-xl font-semibold">Smart filter</h3>
                    <p class="mt-3 text-base leading-7 text-white/55">Only quality setups leave the AI engine.</p>
                </div>
                <div data-reveal class="opacity-0 translate-y-6 transition duration-700 delay-200 ease-out">
                    <p class="font-enDisplay text-5xl font-semibold text-ember-400">02</p>
                    <h3 class="mt-4 text-xl font-semibold">Live path</h3>
                    <p class="mt-3 text-base leading-7 text-white/55">Risk-free moves as the trade develops.</p>
                </div>
                <div data-reveal class="opacity-0 translate-y-6 transition duration-700 delay-300 ease-out">
                    <p class="font-enDisplay text-5xl font-semibold text-white/35">03</p>
                    <h3 class="mt-4 text-xl font-semibold">Honest results</h3>
                    <p class="mt-3 text-base leading-7 text-white/55">Wins and losses stay on the record.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="how" class="bg-mist-50 py-24 md:py-32">
        <div class="mx-auto max-w-6xl px-6 md:px-10">
            <div data-reveal class="opacity-0 translate-y-6 transition duration-700 ease-out md:max-w-2xl">
                <h2 class="font-enDisplay text-4xl font-semibold tracking-tight md:text-5xl">Start in Telegram</h2>
                <p class="mt-5 text-lg leading-8 text-ink-800/70">
                    Free public signals for everyone. VIP unlocks more coverage inside the bot.
                </p>
            </div>

            <ol class="mt-16 space-y-0">
                <li data-reveal class="grid gap-4 border-t border-ink-900/10 py-10 opacity-0 translate-y-6 transition duration-700 delay-100 ease-out md:grid-cols-[8rem_1fr] md:gap-12">
                    <span class="font-enDisplay text-sm font-semibold tracking-[0.2em] text-tide-600 uppercase">Step 01</span>
                    <div>
                        <h3 class="text-2xl font-semibold">Open the bot</h3>
                        <p class="mt-2 max-w-lg text-base leading-7 text-ink-800/65">Send /start — you’re on the free plan instantly.</p>
                    </div>
                </li>
                <li data-reveal class="grid gap-4 border-t border-ink-900/10 py-10 opacity-0 translate-y-6 transition duration-700 delay-200 ease-out md:grid-cols-[8rem_1fr] md:gap-12">
                    <span class="font-enDisplay text-sm font-semibold tracking-[0.2em] text-ember-500 uppercase">Step 02</span>
                    <div>
                        <h3 class="text-2xl font-semibold">Receive free signals</h3>
                        <p class="mt-2 max-w-lg text-base leading-7 text-ink-800/65">Public setups arrive in chat as they’re published.</p>
                    </div>
                </li>
                <li data-reveal class="grid gap-4 border-t border-b border-ink-900/10 py-10 opacity-0 translate-y-6 transition duration-700 delay-300 ease-out md:grid-cols-[8rem_1fr] md:gap-12">
                    <span class="font-enDisplay text-sm font-semibold tracking-[0.2em] text-ink-900/35 uppercase">Step 03</span>
                    <div>
                        <h3 class="text-2xl font-semibold">Go VIP anytime</h3>
                        <p class="mt-2 max-w-lg text-base leading-7 text-ink-800/65">Upgrade from the bot menu when you need more markets.</p>
                    </div>
                </li>
            </ol>

            <div data-reveal class="mt-12 opacity-0 translate-y-6 transition duration-700 delay-200 ease-out">
                <a href="{{ $botEn }}" target="_blank" rel="noopener noreferrer" class="btn-en">
                    Launch English Bot
                </a>
            </div>
        </div>
    </section>

    <footer class="border-t border-ink-900/10 bg-white py-10">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 md:px-10">
            <p class="font-enDisplay text-xl font-semibold text-sea-950">Nova Signal</p>
            <a href="{{ route('home.fa') }}" class="lang-switch border-ink-900/15 text-ink-700 hover:border-tide-500 hover:text-tide-600" aria-label="Persian site">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M3 12h18M12 3c2.5 2.7 3.8 5.8 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.8-3.8-9S9.5 5.7 12 3z"/>
                </svg>
            </a>
        </div>
    </footer>
</body>
</html>
