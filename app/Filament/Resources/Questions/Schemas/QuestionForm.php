<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Forms\Components\Toggle;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Sual Başlığı
                TextInput::make('title')
                    ->label('Sual Başlığı')
                    ->required()
                    ->translatable(),
                // Sual Növü
                Select::make('type')
                    ->options([
                        'mcq' => 'Çox Seçimli',
                        'true_false' => 'Doğru/Yanlış',
                    ])
                    ->required(),

                // Variantlar (Repeater ilə dinamik əlavə edilən variantlar)
                Repeater::make('options')
                    ->label('Variantlar')
                    ->relationship('options')
                    ->schema([
                        FormTextInput::make('option_text')
                            ->label('Variant Mətnini daxil et')
                            ->translatable(),

                        Toggle::make('is_correct')
                            ->label('Düzgün cavabdır?')
                            ->default(false),  // İlk başda düzgün cavab seçilməyib
                    ])
                    ->minItems(2)  // Minimum 2 variant olmalıdır
                    ->maxItems(4)  // Maksimum 4 variant
                    ->defaultItems(2) // Başlanğıc olaraq 2 variant olsun
            ]);
    }
}
