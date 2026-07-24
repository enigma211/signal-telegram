<x-filament-panels::page
    @class([
        'fi-resource-view-record-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
        'fi-resource-record-' . $record->getKey(),
    ])
>
    @php
        $relationManagers = $this->getRelationManagers();
        $hasCombinedRelationManagerTabsWithContent = $this->hasCombinedRelationManagerTabsWithContent();
        $ticketClosed = $record->status === 'closed';
    @endphp

    @if ((! $hasCombinedRelationManagerTabsWithContent) || (! count($relationManagers)))
        @if ($this->hasInfolist())
            {{ $this->infolist }}
        @else
            <div wire:key="{{ $this->getId() }}.forms.{{ $this->getFormStatePath() }}">
                {{ $this->form }}
            </div>
        @endif
    @endif

    @if (count($relationManagers))
        <x-filament-panels::resources.relation-managers
            :active-locale="isset($activeLocale) ? $activeLocale : null"
            :active-manager="$this->activeRelationManager ?? ($hasCombinedRelationManagerTabsWithContent ? null : array_key_first($relationManagers))"
            :content-tab-label="$this->getContentTabLabel()"
            :content-tab-icon="$this->getContentTabIcon()"
            :content-tab-position="$this->getContentTabPosition()"
            :managers="$relationManagers"
            :owner-record="$record"
            :page-class="static::class"
        >
            @if ($hasCombinedRelationManagerTabsWithContent)
                <x-slot name="content">
                    @if ($this->hasInfolist())
                        {{ $this->infolist }}
                    @else
                        {{ $this->form }}
                    @endif
                </x-slot>
            @endif
        </x-filament-panels::resources.relation-managers>
    @endif

    <div class="mt-6">
        @if ($ticketClosed)
            <x-filament::section>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    این تیکت بسته شده است. برای گفتگوی جدید کاربر باید دوباره از ربات پشتیبانی را باز کند.
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <x-slot name="heading">
                    ارسال پاسخ به کاربر
                </x-slot>
                <x-slot name="description">
                    پاسخ شما همین‌جا ثبت و برای کاربر در تلگرام ارسال می‌شود.
                </x-slot>

                <form wire:submit="sendReply" class="space-y-4">
                    {{ $this->replyForm }}

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <x-filament::button
                            type="submit"
                            color="success"
                            icon="heroicon-o-paper-airplane"
                            wire:loading.attr="disabled"
                        >
                            ارسال پاسخ
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
