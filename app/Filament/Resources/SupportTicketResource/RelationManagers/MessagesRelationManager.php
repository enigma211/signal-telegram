<?php

namespace App\Filament\Resources\SupportTicketResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'پیام‌ها';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('زمان')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sender')
                    ->label('فرستنده')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'admin' ? 'ادمین' : 'کاربر')
                    ->color(fn (string $state): string => $state === 'admin' ? 'success' : 'info'),
                Tables\Columns\TextColumn::make('admin.name')
                    ->label('ادمین')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('body')
                    ->label('متن')
                    ->wrap()
                    ->searchable(),
            ])
            ->defaultSort('id')
            ->paginated(false)
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
