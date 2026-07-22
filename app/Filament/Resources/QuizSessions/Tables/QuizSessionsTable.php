<?php

namespace App\Filament\Resources\QuizSessions\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class QuizSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Müştəri')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('customer.phone')
                    ->label('Telefon')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('quiz.title')
                    ->label('Kampaniya')
                    ->sortable()
                    ->searchable()
                    ->limit(60),

                TextColumn::make('store.name')
                    ->label('Filial')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('started_at')
                    ->label('Başlama')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('finished_at')
                    ->label('Bitmə')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('score_pct')
                    ->label('Bal %')
                    ->numeric()
                    ->suffix('%')
                    ->sortable(),

                IconColumn::make('is_passed')
                    ->label('Keçib?')
                    ->boolean(),

                TextColumn::make('channel')
                    ->label('Kanal')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ip')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_passed')->label('Keçib?'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([])
            ->defaultSort('started_at', 'desc');
    }
}
