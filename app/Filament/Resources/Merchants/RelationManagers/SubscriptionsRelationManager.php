<?php

namespace App\Filament\Resources\Merchants\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    protected static ?string $title = 'Abunəlik / Gəlir tarixçəsi';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plan_name')->label('Paket'),
                TextColumn::make('amount')
                    ->label('Məbləğ')
                    ->formatStateUsing(fn ($state, $record) => number_format((float) $state, 2) . ' ' . $record->currency)
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->label('Cəmi')),
                TextColumn::make('starts_at')->label('Başlanğıc')->dateTime('d.m.Y'),
                TextColumn::make('ends_at')->label('Bitmə')->dateTime('d.m.Y'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'active'    => 'Aktiv',
                        'expired'   => 'Bitib',
                        'cancelled' => 'Ləğv',
                        default     => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'active'    => 'success',
                        'expired'   => 'gray',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('creator.name')->label('Təyin edən')->placeholder('-'),
                TextColumn::make('note')->label('Qeyd')->placeholder('-')->limit(40),
                TextColumn::make('created_at')->label('Tarix')->dateTime('d.m.Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
