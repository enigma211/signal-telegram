<?php

namespace App\Filament\Resources;

use App\Enums\MarketType;
use App\Filament\Resources\TelegramChannelResource\Pages;
use App\Models\TelegramChannel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TelegramChannelResource extends Resource
{
    protected static ?string $model = TelegramChannel::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'کانال‌های تلگرام';

    protected static ?string $modelLabel = 'کانال تلگرام';

    protected static ?string $pluralModelLabel = 'کانال‌های تلگرام';

    protected static ?string $navigationGroup = 'تلگرام';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات کانال')->schema([
                    Forms\Components\Select::make('telegram_bot_id')
                        ->label('ربات مرتبط')
                        ->relationship('bot', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('رباتی که ادمین کانال است و سیگنال را ارسال می‌کند.'),
                    Forms\Components\TextInput::make('title')
                        ->label('عنوان کانال')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('chat_id')
                        ->label('Chat ID کانال')
                        ->required()
                        ->maxLength(255)
                        ->helperText('مثال: -1001234567890 یا @channelusername'),
                    Forms\Components\TextInput::make('username')
                        ->label('یوزرنیم کانال')
                        ->prefix('@')
                        ->maxLength(255),
                    Forms\Components\Select::make('market_type')
                        ->label('بازار هدف')
                        ->options(collect(MarketType::cases())->mapWithKeys(
                            fn (MarketType $case) => [$case->value => $case->label()]
                        ))
                        ->nullable()
                        ->placeholder('همه بازارها')
                        ->helperText('خالی = دریافت همه سیگنال‌ها'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('فعال برای پخش سیگنال')
                        ->default(true)
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->label('یادداشت')
                        ->rows(3)
                        ->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bot.name')
                    ->label('ربات')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bot.language')
                    ->label('زبان')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                Tables\Columns\TextColumn::make('chat_id')
                    ->label('Chat ID')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('یوزرنیم')
                    ->formatStateUsing(fn (?string $state): string => $state ? '@'.$state : '—'),
                Tables\Columns\TextColumn::make('market_type')
                    ->label('بازار')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? 'همه')
                    ->color(fn ($state) => match ($state) {
                        MarketType::Forex => 'warning',
                        MarketType::Crypto => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('telegram_bot_id')
                    ->label('ربات')
                    ->relationship('bot', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('فعال'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف'),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTelegramChannels::route('/'),
            'create' => Pages\CreateTelegramChannel::route('/create'),
            'edit' => Pages\EditTelegramChannel::route('/{record}/edit'),
        ];
    }
}
