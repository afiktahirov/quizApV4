<?php

namespace App\Filament\Resources\Quizzes\RelationManagers;

use App\Filament\Resources\QuizSessions\QuizSessionResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;


class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $relatedResource = QuizSessionResource::class;

    /**
     * Get the query for the table.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getTableQuery(): Builder
    {
        $query = $this  ->getRelationship()->getQuery();

        if (!auth()->user()->is_admin) {
            $merchantId = auth()->user()->merchant_id;

            return $query->where('merchant_id', $merchantId);
        }

        return $query->with('quiz');
    }
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
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}