<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SignalUpdateResource\Pages;
use App\Models\SignalUpdate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SignalUpdateResource extends Resource
{
    protected static ?string $model = SignalUpdate::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'آپدیت سیگنال‌ها';

    protected static ?string $modelLabel = 'آپدیت سیگنال';

    protected static ?string $pluralModelLabel = 'آپدیت سیگنال‌ها';

    protected static ?string $navigationGroup = 'سیگنال‌ها';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('پیام آپدیت')->schema([
                    Forms\Components\Select::make('signal_id')
                        ->label('سیگنال')
                        ->relationship('signal', 'symbol')
                        ->getOptionLabelFromRecordUsing(
                            fn ($record): string => "#{$record->id} — {$record->symbol}"
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Textarea::make('update_message_fa')
                        ->label('پیام فارسی')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('update_message_en')
                        ->label('پیام انگلیسی')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('signal.symbol')
                    ->label('نماد')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('update_message_fa')
                    ->label('پیام فارسی')
                    ->limit(40)
                    ->wrap(),
                Tables\Columns\TextColumn::make('update_message_en')
                    ->label('پیام انگلیسی')
                    ->limit(40)
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('زمان')
                    ->jalaliDateTime()
                    ->sortable(),
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
            'index' => Pages\ListSignalUpdates::route('/'),
            'create' => Pages\CreateSignalUpdate::route('/create'),
            'edit' => Pages\EditSignalUpdate::route('/{record}/edit'),
        ];
    }
}
