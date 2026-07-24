<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCodeResource\Pages;
use App\Models\PromoCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'کدهای تخفیف';

    protected static ?string $modelLabel = 'کد تخفیف';

    protected static ?string $pluralModelLabel = 'کدهای تخفیف';

    protected static ?string $navigationGroup = 'کاربران و مالی';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات کد تخفیف')->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('کد')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->extraInputAttributes(['style' => 'text-transform:uppercase']),
                    Forms\Components\TextInput::make('discount_percentage')
                        ->label('درصد تخفیف')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->suffix('%')
                        ->required(),
                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('تاریخ انقضا'),
                    Forms\Components\TextInput::make('max_uses')
                        ->label('حداکثر استفاده')
                        ->numeric()
                        ->default(100)
                        ->required(),
                    Forms\Components\TextInput::make('current_uses')
                        ->label('تعداد استفاده‌شده')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('کد')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label('تخفیف')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('انقضا')
                    ->jalaliDateTime()
                    ->placeholder('بدون محدودیت'),
                Tables\Columns\TextColumn::make('current_uses')
                    ->label('استفاده')
                    ->formatStateUsing(fn (PromoCode $record): string => "{$record->current_uses} / {$record->max_uses}"),
                Tables\Columns\IconColumn::make('is_valid')
                    ->label('معتبر')
                    ->boolean()
                    ->getStateUsing(fn (PromoCode $record): bool => $record->isValid()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('ایجاد')
                    ->jalaliDate()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}
