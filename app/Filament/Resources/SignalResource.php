<?php

namespace App\Filament\Resources;

use App\Enums\MarketType;
use App\Enums\SignalResult;
use App\Enums\SignalStatus;
use App\Enums\TargetAudience;
use App\Filament\Resources\SignalResource\Pages;
use App\Jobs\BroadcastSignalJob;
use App\Models\Signal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SignalResource extends Resource
{
    protected static ?string $model = Signal::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'سیگنال‌ها';

    protected static ?string $modelLabel = 'سیگنال';

    protected static ?string $pluralModelLabel = 'سیگنال‌ها';

    protected static ?string $navigationGroup = 'سیگنال‌ها';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات سیگنال')->schema([
                    Forms\Components\Select::make('market_type')
                        ->label('بازار')
                        ->options(collect(MarketType::cases())->mapWithKeys(
                            fn (MarketType $case) => [$case->value => $case->label()]
                        ))
                        ->required(),
                    Forms\Components\TextInput::make('symbol')
                        ->label('نماد')
                        ->required()
                        ->placeholder('XAUUSD'),
                    Forms\Components\TextInput::make('entry_price')
                        ->label('ورود')
                        ->required(),
                    Forms\Components\TextInput::make('tp1')
                        ->label('هدف ۱')
                        ->required(),
                    Forms\Components\TextInput::make('tp2')
                        ->label('هدف ۲'),
                    Forms\Components\TextInput::make('tp3')
                        ->label('هدف ۳'),
                    Forms\Components\TextInput::make('stop_loss')
                        ->label('استاپ‌لاس')
                        ->required(),
                    Forms\Components\TextInput::make('image_path')
                        ->label('مسیر تصویر'),
                    Forms\Components\Select::make('target_audience')
                        ->label('مخاطب')
                        ->options(collect(TargetAudience::cases())->mapWithKeys(
                            fn (TargetAudience $case) => [$case->value => $case->label()]
                        ))
                        ->required()
                        ->helperText('«همه کاربران» = سیگنال تبلیغاتی رایگان برای همه. «فقط VIP» = سیگنال اختصاصی.'),
                    Forms\Components\Select::make('status')
                        ->label('وضعیت ارسال')
                        ->options(collect(SignalStatus::cases())->mapWithKeys(
                            fn (SignalStatus $case) => [$case->value => $case->label()]
                        ))
                        ->default(SignalStatus::Pending->value)
                        ->required()
                        ->helperText('با ایجاد سیگنال، ارسال خودکار انجام می‌شود و وضعیت به «ارسال‌شده» به‌روز می‌گردد.'),
                    Forms\Components\Select::make('result')
                        ->label('نتیجه')
                        ->options(collect(SignalResult::cases())->mapWithKeys(
                            fn (SignalResult $case) => [$case->value => $case->label()]
                        ))
                        ->default(SignalResult::Pending->value)
                        ->required()
                        ->helperText('با تغییر نتیجه از حالت در انتظار، پیام نتیجه به تلگرام ارسال می‌شود.'),
                    Forms\Components\TextInput::make('pips_gained')
                        ->label('پیپ')
                        ->numeric(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('symbol')
                    ->label('نماد')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('market_type')
                    ->label('بازار')
                    ->badge()
                    ->formatStateUsing(fn (MarketType $state): string => $state->label())
                    ->color(fn (MarketType $state): string => match ($state) {
                        MarketType::Forex => 'warning',
                        MarketType::Crypto => 'success',
                    }),
                Tables\Columns\TextColumn::make('entry_price')
                    ->label('ورود'),
                Tables\Columns\TextColumn::make('tp1')
                    ->label('TP1'),
                Tables\Columns\TextColumn::make('stop_loss')
                    ->label('SL'),
                Tables\Columns\TextColumn::make('target_audience')
                    ->label('مخاطب')
                    ->badge()
                    ->formatStateUsing(fn (TargetAudience $state): string => $state->label()),
                Tables\Columns\TextColumn::make('status')
                    ->label('ارسال')
                    ->badge()
                    ->formatStateUsing(fn (SignalStatus $state): string => $state->label())
                    ->color(fn (SignalStatus $state): string => match ($state) {
                        SignalStatus::Pending => 'warning',
                        SignalStatus::Broadcasted => 'success',
                    }),
                Tables\Columns\TextColumn::make('result')
                    ->label('نتیجه')
                    ->badge()
                    ->formatStateUsing(fn (SignalResult $state): string => $state->label())
                    ->color(fn (SignalResult $state): string => match ($state) {
                        SignalResult::Pending => 'gray',
                        SignalResult::Win => 'success',
                        SignalResult::Loss => 'danger',
                        SignalResult::Neutral => 'warning',
                    }),
                Tables\Columns\TextColumn::make('pips_gained')
                    ->label('پیپ')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('updates_count')
                    ->counts('updates')
                    ->label('آپدیت‌ها'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('ایجاد')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('market_type')
                    ->label('بازار')
                    ->options(collect(MarketType::cases())->mapWithKeys(
                        fn (MarketType $case) => [$case->value => $case->label()]
                    )),
                Tables\Filters\SelectFilter::make('result')
                    ->label('نتیجه')
                    ->options(collect(SignalResult::cases())->mapWithKeys(
                        fn (SignalResult $case) => [$case->value => $case->label()]
                    )),
            ])
            ->actions([
                Tables\Actions\Action::make('broadcast')
                    ->label('ارسال')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Signal $record): void {
                        BroadcastSignalJob::dispatch($record);
                        Notification::make()
                            ->title('سیگنال به صف ارسال اضافه شد')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListSignals::route('/'),
            'create' => Pages\CreateSignal::route('/create'),
            'edit' => Pages\EditSignal::route('/{record}/edit'),
        ];
    }
}
