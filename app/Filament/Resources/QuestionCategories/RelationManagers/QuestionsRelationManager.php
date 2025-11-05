<?php

namespace App\Filament\Resources\QuestionCategories\RelationManagers;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';
    protected static ?string $relatedResource = QuestionResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Sual Başlığı')
                    ->state(function ($record) {
                        $t = $record->title;
                        return is_array($t) ? ($t['az'] ?? reset($t) ?? '') : (string) $t;
                    })
                    ->searchable()
                    ->limit(60),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Növ')
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->multiple()
                    ->recordSelectSearchColumns(['title', 'id']),

//                CreateAction::make()
                ])
            ->recordActions([
                // Bu kateqoriyadan ayırmaq
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
