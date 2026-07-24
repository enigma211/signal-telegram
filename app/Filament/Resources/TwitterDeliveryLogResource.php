<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TwitterDeliveryLogResource\Pages;
use App\Models\TwitterDeliveryLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TwitterDeliveryLogResource extends Resource
{
    protected static ?string $model = TwitterDeliveryLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'لاگ خطای توییتر';

    protected static ?string $modelLabel = 'لاگ توییتر';

    protected static ?string $pluralModelLabel = 'لاگ‌های توییتر';

    protected static ?string $navigationGroup = 'پیام‌رسانی';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = TwitterDeliveryLog::query()->where('status', 'failed')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('context')->label('نوع')->disabled(),
                Forms\Components\TextInput::make('tweet_id')->label('Tweet ID')->disabled(),
                Forms\Components\Textarea::make('message_text')->label('متن')->rows(6)->disabled()->columnSpanFull(),
                Forms\Components\Textarea::make('error_message')->label('خطا')->rows(3)->disabled()->columnSpanFull(),
                Forms\Components\TextInput::make('status')->label('وضعیت')->disabled(),
                Forms\Components\TextInput::make('attempts')->label('تلاش‌ها')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('context')
                    ->label('نوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'signal' => 'سیگنال',
                        'signal_result' => 'نتیجه',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'failed' ? 'danger' : 'success')
                    ->formatStateUsing(fn (string $state): string => $state === 'failed' ? 'ناموفق' : 'ارسال‌شده'),
                Tables\Columns\TextColumn::make('message_text')->label('متن')->limit(40)->placeholder('—'),
                Tables\Columns\TextColumn::make('error_message')->label('خطا')->limit(50)->placeholder('—')->wrap(),
                Tables\Columns\TextColumn::make('tweet_id')->label('Tweet ID')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('attempts')->label('تلاش'),
                Tables\Columns\TextColumn::make('created_at')->label('زمان')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        'failed' => 'ناموفق',
                        'sent' => 'ارسال‌شده',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('مشاهده'),
            ])
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTwitterDeliveryLogs::route('/'),
            'view' => Pages\ViewTwitterDeliveryLog::route('/{record}'),
        ];
    }
}
