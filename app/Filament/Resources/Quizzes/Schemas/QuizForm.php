<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class QuizForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('quiz_category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->label('Kateqoriya'),
                Select::make('merchants')
                    ->label('Merchants')
                    ->multiple()
                    ->relationship('merchants', 'name')
                    ->searchable()
                    ->visible(fn () => auth()->user()?->role === 'super_admin')
                    ->preload(),
                TextInput::make('title')->label('Başlıq')->required()->maxLength(255),
                TextInput::make('total_questions')->numeric()->minValue(1)->default(5)->label('Sual sayı'),
                TextInput::make('pass_threshold_pct')->numeric()->minValue(0)->maxValue(100)->default(60)->label('Keçid %'),
                TextInput::make('time_per_question_sec')->numeric()->minValue(0)->nullable()->label('Sual üçün vaxt (s)'),
                Select::make('status')->options(['active' => 'Aktiv', 'inactive' => 'Deaktiv'])->default('active')->required(),
            ]);
    }
}
