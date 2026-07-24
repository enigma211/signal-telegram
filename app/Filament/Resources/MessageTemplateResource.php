<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageTemplateResource\Pages;
use App\Models\MessageTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MessageTemplateResource extends Resource
{
    protected static ?string $model = MessageTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'قالب پیام تلگرام';

    protected static ?string $modelLabel = 'قالب پیام';

    protected static ?string $pluralModelLabel = 'قالب‌های پیام';

    protected static ?string $navigationGroup = 'پیام‌رسانی';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات قالب')->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('نام نمایشی')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('key')
                        ->label('کلید سیستم')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('این کلید توسط سیستم استفاده می‌شود و قابل تغییر نیست.'),
                    Forms\Components\Placeholder::make('placeholders_help_display')
                        ->label('متغیرهای قابل استفاده')
                        ->content(fn (?MessageTemplate $record): string => $record?->placeholders_help
                            ?? 'پس از ذخیره، راهنمای متغیرها اینجا نمایش داده می‌شود.'),
                ]),
                Forms\Components\Section::make('متن پیام‌ها')->schema([
                    Forms\Components\Textarea::make('body_fa')
                        ->label('متن فارسی (ربات FA)')
                        ->required()
                        ->rows(14)
                        ->columnSpanFull()
                        ->helperText('از Markdown تلگرام پشتیبانی می‌شود. متغیرها را داخل {} بنویسید.'),
                    Forms\Components\Textarea::make('body_en')
                        ->label('متن انگلیسی (ربات EN)')
                        ->required()
                        ->rows(14)
                        ->columnSpanFull()
                        ->helperText('Supports Telegram Markdown. Use placeholders inside {}.'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('key')
                    ->label('کلید')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('body_fa')
                    ->label('پیش‌نمایش فارسی')
                    ->limit(40)
                    ->wrap(),
                Tables\Columns\TextColumn::make('body_en')
                    ->label('پیش‌نمایش انگلیسی')
                    ->limit(40)
                    ->wrap(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین ویرایش')
                    ->jalaliDateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش'),
            ])
            ->bulkActions([])
            ->defaultSort('id');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessageTemplates::route('/'),
            'edit' => Pages\EditMessageTemplate::route('/{record}/edit'),
        ];
    }
}
