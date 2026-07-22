<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use App\Models\Merchant;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Yalnız super admin başqa merchant adına kampaniya yarada bilər;
                // merchant istifadəçisi üçün merchant_id avtomatik təyin olunur (CreateQuiz).
                Select::make('merchant_id')
                    ->label('Müəssisə')
                    ->options(Merchant::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->visible(fn () => Filament::auth()->user()?->is_admin ?? false),

                Select::make('quiz_category_id')
                    ->relationship('category', 'name')
                    ->label('Kateqoriya')
                    ->nullable(),

                TextInput::make('title')->label('Başlıq')->required()->maxLength(255),

                TextInput::make('total_questions')
                    ->numeric()->minValue(1)->maxValue(50)->default(5)
                    ->label('Sual sayı')
                    ->helperText('Müştəriyə kampaniyaya bağlanmış suallardan bu qədəri random verilir'),

                TextInput::make('pass_threshold_pct')
                    ->numeric()->minValue(0)->maxValue(100)->default(60)
                    ->label('Keçid %'),

                TextInput::make('time_per_question_sec')
                    ->numeric()->minValue(0)->nullable()
                    ->label('Sual üçün vaxt (s)'),

                Select::make('status')
                    ->options([
                        'draft'    => 'Qaralama',
                        'active'   => 'Aktiv',
                        'archived' => 'Arxiv',
                    ])
                    ->default('draft')
                    ->required()
                    ->label('Status'),

                // ---- Endirim rejimi ----
                Select::make('reward_mode')
                    ->label('Endirim rejimi')
                    ->options([
                        'flat'   => 'Sabit (keçid faizini keçən eyni kuponu alır)',
                        'tiered' => 'Pilləli (düzgün cavab sayına görə fərqli endirim)',
                    ])
                    ->default('flat')
                    ->required()
                    ->live()
                    ->helperText('Sabit rejimdə kupon dəyəri müəssisənin ayarlarından götürülür.'),

                Repeater::make('rewardTiers')
                    ->label('Endirim pillələri')
                    ->relationship('rewardTiers')
                    ->visible(fn ($get) => $get('reward_mode') === 'tiered')
                    ->helperText('Müştəri çatdığı ən yüksək pillənin endirimini alır. Məs: 3 düz → 5%, 4 düz → 10%, 5 düz → 15%.')
                    ->schema([
                        TextInput::make('min_correct')
                            ->label('Min. düzgün cavab')
                            ->numeric()->minValue(1)->required(),
                        Select::make('discount_type')
                            ->label('Növ')
                            ->options([
                                'percent' => 'Faiz (%)',
                                'amount'  => 'Məbləğ (AZN)',
                            ])
                            ->default('percent')
                            ->required(),
                        TextInput::make('value')
                            ->label('Dəyər')
                            ->numeric()->minValue(0)->required(),
                    ])
                    ->columns(3)
                    ->defaultItems(1)
                    ->addActionLabel('Pillə əlavə et')
                    ->orderColumn('position'),
            ]);
    }
}
