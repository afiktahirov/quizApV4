<?php

namespace App\Filament\Resources\QuizSessions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuizSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Mağaza (relation)
                TextColumn::make('store.name')
                    ->label('Mağaza')
                    ->sortable()
                    ->searchable(),

                // Quiz (relation)
                TextColumn::make('quiz.title')
                    ->label('Kampaniya')
                    ->sortable()
                    ->searchable()
                    ->limit(60),

                // İstifadəçi (relation)
                TextColumn::make('user.name')
                    ->label('İstifadəçi')
                    ->sortable()
                    ->searchable(),

                // Başlama / Bitmə
                TextColumn::make('started_at')
                    ->label('Başlama')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('finished_at')
                    ->label('Bitmə')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                // Bal faizi
                TextColumn::make('score_pct')
                    ->label('Bal %')
                    ->numeric()
                    ->suffix('%')
                    ->sortable(),

                // Keçid vəziyyəti
                IconColumn::make('is_passed')
                    ->label('Keçib?')
                    ->boolean(),

                // Kanal / IP / Cihaz izi
                TextColumn::make('channel')
                    ->label('Kanal')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('ip')
                    ->label('IP')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('device_fingerprint')
                    ->label('Cihaz izi')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                // istəsən bura keçid üçün TernaryFilter, tarix aralığı və s. əlavə edə bilərsən
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
