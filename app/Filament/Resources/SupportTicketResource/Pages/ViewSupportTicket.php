<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Services\SupportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected static string $view = 'filament.resources.support-ticket-resource.pages.view-support-ticket';

    protected static ?string $title = 'مشاهده تیکت پشتیبانی';

    public ?array $replyData = [];

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->replyForm->fill([
            'body' => '',
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'تیکت #'.$this->record->getKey();
    }

    public function replyForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('body')
                    ->hiddenLabel()
                    ->placeholder('پاسخ خود را اینجا بنویسید…')
                    ->required()
                    ->rows(5)
                    ->autosize()
                    ->columnSpanFull(),
            ])
            ->statePath('replyData');
    }

    /**
     * @return array<int, string>
     */
    protected function getForms(): array
    {
        return [
            'form',
            'replyForm',
        ];
    }

    public function sendReply(): void
    {
        if ($this->record->status === 'closed') {
            Notification::make()
                ->title('این تیکت بسته است')
                ->warning()
                ->send();

            return;
        }

        $data = $this->replyForm->getState();

        app(SupportService::class)->replyAsAdmin(
            $this->record,
            $data['body'],
            auth()->user()
        );

        Notification::make()
            ->title('پاسخ ارسال شد')
            ->success()
            ->send();

        $this->redirect(static::getUrl(['record' => $this->record]), navigate: true);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('close')
                ->label('بستن تیکت')
                ->color('gray')
                ->icon('heroicon-o-lock-closed')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status !== 'closed')
                ->action(function (): void {
                    app(SupportService::class)->closeTicket($this->record);
                    Notification::make()->title('تیکت بسته شد')->success()->send();
                    $this->redirect(static::getUrl(['record' => $this->record]), navigate: true);
                }),
            Actions\EditAction::make()->label('ویرایش'),
        ];
    }
}
