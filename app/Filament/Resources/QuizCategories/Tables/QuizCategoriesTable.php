<?php

namespace App\Filament\Resources\QuizCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class QuizCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')->searchable(),
                TextColumn::make('name')->label('Ad')->searchable()->sortable()->limit(60),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors(['success' => 'active', 'danger' => 'inactive'])
                    ->sortable(),
                TextColumn::make('quizzes_count')
                    ->counts('quizzes')
                    ->label('Kampanya sayı'),
                TextColumn::make('created_at')->dateTime('d.m.Y H:i')->label('Yaradıldı')->since(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['active' => 'Aktiv', 'inactive' => 'Deaktiv']),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
