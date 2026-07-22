<?php

namespace App\Filament\Resources\Quizzes\Tables;

use App\Filament\Resources\Quizzes\QuizResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuizzesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Başlıq')
                    ->searchable(),
                TextColumn::make('merchant.name')
                    ->label('Müəssisə')
                    ->visible(fn () => Filament::auth()->user()?->is_admin ?? false)
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kateqoriya')
                    ->sortable(),
                TextColumn::make('total_questions')
                    ->numeric()
                    ->label('Sual sayı')
                    ->sortable(),
                TextColumn::make('pass_threshold_pct')
                    ->numeric()
                    ->label('Keçid %')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'draft'    => 'warning',
                        'archived' => 'gray',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active'   => 'Aktiv',
                        'draft'    => 'Qaralama',
                        'archived' => 'Arxiv',
                        default    => $state,
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft'    => 'Qaralama',
                        'active'   => 'Aktiv',
                        'archived' => 'Arxiv',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn ($record) => QuizResource::ownsRecord($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
