<?php

namespace App\Filament\Resources\Quizzes\RelationManagers;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use App\Models\Question;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\TextInput;


class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $relatedResource = QuestionResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('title')
                    ->label('Sual')
                    ->state(fn (Question $r) => is_array($r->title)
                        ? ($r->title['az'] ?? reset($r->title) ?? '')
                        : (string) $r->title)
                    ->searchable()
                    ->limit(80),
                TextColumn::make('pivot.weight')->label('Çəki')->sortable(),
                BadgeColumn::make('type')->label('Növ'),
            ])
            ->headerActions([
                // Mövcud sualı attach + pivot 'weight'
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['title->az', 'id'])
                    ->multiple()
                    ->schema(fn (AttachAction $action) => [
                        $action->getRecordSelect(),
                        TextInput::make('weight')
                            ->numeric()->minValue(0)->default(1)->label('Çəki'),
                    ]),
                // Yeni sual yarat və bu quiza qoş
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $owner = $this->getOwnerRecord(); // Quiz
                        $data['merchant_id'] = $owner->merchant_id ?? $data['merchant_id'] ?? null;
                        return $data;
                    })
                    ->using(function (array $data, RelationManager $livewire) {
                        $question = Question::create($data);
                        // pivot-a default weight ilə qoş
                        $livewire->getRelationship()
                            ->syncWithoutDetaching([$question->getKey() => ['weight' => 1]]);
                        return $question;
                    }),
            ])
            ->recordActions([
                // pivot 'weight' redaktəsi
                EditAction::make()
                    ->form([
                        TextInput::make('pivot.weight')
                            ->numeric()->minValue(0)->label('Çəki'),
                    ]),
                DetachAction::make()->label('Ayır'),
            ])
            ->toolbarActions([
//                BulkActionGroup::make([DetachBulkAction::make()]),
            ]);
    }
}
