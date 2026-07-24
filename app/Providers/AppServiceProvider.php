<?php

namespace App\Providers;

use Carbon\Carbon;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('fa');

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => <<<'HTML'
                <link rel="preconnect" href="https://fonts.googleapis.com">
                <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
                <style>
                    :root { --font-family: 'Vazirmatn', ui-sans-serif, system-ui, sans-serif; }
                    html { direction: rtl; }
                    body { font-family: 'Vazirmatn', ui-sans-serif, system-ui, sans-serif; }

                    /* دکمه جمع/باز کردن سایدبار در RTL همیشه دیده شود */
                    .fi-sidebar-close-overlay,
                    .fi-topbar .fi-topbar-open-sidebar-btn,
                    .fi-topbar .fi-topbar-close-sidebar-btn,
                    .fi-topbar button[aria-label],
                    .fi-sidebar-header button {
                        display: inline-flex !important;
                    }
                </style>
                <script>document.documentElement.setAttribute('dir', 'rtl'); document.documentElement.setAttribute('lang', 'fa');</script>
            HTML
        );
    }
}
