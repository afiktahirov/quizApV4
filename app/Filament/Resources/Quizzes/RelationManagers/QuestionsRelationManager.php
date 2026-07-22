<?php

namespace App\Filament\Resources\Quizzes\RelationManagers;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use App\Models\Question;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $relatedResource = QuestionResource::class;

    protected static ?string $title = 'Suallar';

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
                BadgeColumn::make('merchant_id')
                    ->label('Mənbə')
                    ->formatStateUsing(fn (Question $r) => $r->merchant_id === null ? 'Hazır baza' : 'Öz sualı')
                    ->colors(['info']),
                BadgeColumn::make('type')->label('Növ'),
            ])
            ->headerActions([
                // Hazır bazadan və ya öz suallarından seç
                AttachAction::make()
                    ->label('Bazadan sual seç')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['title->az', 'id'])
                    ->multiple()
                    // Merchant yalnız qlobal bazanı + öz suallarını görür
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        $user = Filament::auth()->user();

                        $query->where('is_active', true);

                        if (! ($user?->is_admin ?? false)) {
                            $query->where(function (Builder $q) use ($user) {
                                $q->whereNull('merchant_id')
                                  ->orWhere('merchant_id', $user?->merchant_id);
                            });
                        }

                        return $query;
                    })
                    ->schema(fn (AttachAction $action) => [
                        $action->getRecordSelect(),
                        TextInput::make('weight')
                            ->numeric()->minValue(0)->default(1)->label('Çəki'),
                    ]),
                // Yeni sual yarat və bu kampaniyaya qoş
                CreateAction::make()
                    ->label('Yeni sual yarat')
                    // Paket sual limiti dolubsa merchant yeni sual yarada bilməz (admin həmişə bilər)
                    ->visible(function () {
                        $user = Filament::auth()->user();
                        if ($user?->is_admin) {
                            return true;
                        }
                        return (bool) $user?->merchant?->canAdd('questions');
                    })
                    ->mutateDataUsing(function (array $data): array {
                        $owner = $this->getOwnerRecord(); // Quiz
                        $data['merchant_id'] = $owner->merchant_id;
                        return $data;
                    })
                    ->using(function (array $data, RelationManager $livewire) {
                        $options = $data['options'] ?? [];
                        unset($data['options']);

                        $question = Question::create($data);

                        foreach ($options as $i => $opt) {
                            $question->options()->create([
                                'option_text' => $opt['option_text'] ?? '',
                                'is_correct'  => (bool) ($opt['is_correct'] ?? false),
                                'position'    => $i + 1,
                            ]);
                        }

                        $livewire->getRelationship()
                            ->syncWithoutDetaching([$question->getKey() => ['weight' => 1]]);

                        return $question;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(function (Question $record) {
                        $user = Filament::auth()->user();
                        // qlobal bazadakı sualı yalnız super admin redaktə edə bilər
                        return ($user?->is_admin ?? false)
                            || ($record->merchant_id !== null && $record->merchant_id === $user?->merchant_id);
                    })
                    ->form([
                        TextInput::make('pivot.weight')
                            ->numeric()->minValue(0)->label('Çəki'),
                    ]),
                DetachAction::make()->label('Ayır'),
            ])
            ->toolbarActions([]);
    }
}
