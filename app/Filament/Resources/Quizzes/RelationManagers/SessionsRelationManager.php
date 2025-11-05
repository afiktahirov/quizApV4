<?php

namespace App\Filament\Resources\Quizzes\RelationManagers;

use App\Filament\Resources\QuizSessions\QuizSessionResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;


class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $relatedResource = QuizSessionResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('user.name')
                    ->label('İstifadəçi')
                    ->state(fn ($record) => $record->user?->name ?? '-'),
                TextColumn::make('score_pct')->label('Bal %'),
                IconColumn::make('is_passed')->boolean()->label('Keçib?'),
                TextColumn::make('started_at')->dateTime('d.m.Y H:i')->label('Başlama'),
                TextColumn::make('finished_at')->dateTime('d.m.Y H:i')->label('Bitmə'),
//                TextColumn::make('channel')->label('Kanal'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
