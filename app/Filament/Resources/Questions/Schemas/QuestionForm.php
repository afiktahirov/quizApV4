<?php

namespace App\Filament\Resources\Questions\Schemas;

use Closure;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Builder;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Sual Başlığı')
                    ->required()
                    ->translatable(),

                Select::make('type')
                    ->label('Sual Növü')
                    ->options([
                        'mcq'        => 'Çox Seçimli',
                        'true_false' => 'Doğru/Yanlış',
                    ])
                    ->default('mcq')
                    ->required(),

                Select::make('difficulty')
                    ->label('Çətinlik')
                    ->options([
                        'easy'   => 'Asan',
                        'medium' => 'Orta',
                        'hard'   => 'Çətin',
                    ])
                    ->nullable(),

                Select::make('questionCategories')
                    ->label('Kateqoriyalar')
                    ->relationship(
                        'questionCategories',
                        'name',
                        // merchant öz + qlobal kateqoriyaları görür
                        function (Builder $query) {
                            $user = Filament::auth()->user();
                            if ($user?->is_admin) {
                                return $query;
                            }
                            return $query->where(function (Builder $q) use ($user) {
                                $q->whereNull('merchant_id')
                                  ->orWhere('merchant_id', $user?->merchant_id);
                            });
                        }
                    )
                    ->multiple()
                    ->preload(),

                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),

                Repeater::make('options')
                    ->label('Variantlar')
                    ->relationship('options')
                    ->schema([
                        TextInput::make('option_text')
                            ->label('Variant mətni')
                            ->required()
                            ->translatable(),

                        Toggle::make('is_correct')
                            ->label('Düzgün cavabdır?')
                            ->default(false),
                    ])
                    ->minItems(2)
                    ->maxItems(6)
                    ->defaultItems(2)
                    // ən azı bir düzgün cavab seçilməlidir
                    ->rules([
                        fn (): Closure => function (string $attribute, $value, Closure $fail) {
                            $hasCorrect = collect($value ?? [])
                                ->contains(fn ($opt) => ! empty($opt['is_correct']));

                            if (! $hasCorrect) {
                                $fail('Ən azı bir variant düzgün cavab kimi işarələnməlidir.');
                            }
                        },
                    ]),
            ]);
    }
}
