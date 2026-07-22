<?php

namespace App\Filament\Resources\Questions\Tables;

use App\Filament\Resources\Questions\QuestionResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Sual Başlığı')
                    ->searchable()
                    ->sortable()
                    ->state(function ($record) {
                        if (is_array($record->title)) {
                            return $record->title['az'] ?? reset($record->title) ?? '';
                        }
                        return $record->title;
                    })
                    ->limit(60),

                BadgeColumn::make('merchant_id')
                    ->label('Mənbə')
                    ->state(fn ($record) => $record->merchant_id === null ? 'Hazır baza' : ($record->merchant?->name ?? 'Öz sualı'))
                    ->colors(['info']),

                BadgeColumn::make('type')
                    ->label('Sual Növü')
                    ->colors([
                        'primary' => 'mcq',
                        'success' => 'true_false',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'mcq'        => 'Çoxseçimli',
                        'true_false' => 'Doğru / Yanlış',
                        default      => ucfirst($state),
                    })
                    ->sortable(),

                TextColumn::make('options_count')
                    ->label('Variant sayı')
                    ->counts('options')
                    ->sortable(),

                TextColumn::make('correct_options')
                    ->label('Düzgün cavab(lar)')
                    ->state(function ($record) {
                        $locale = app()->getLocale();
                        return $record->options
                            ->where('is_correct', true)
                            ->map(function ($opt) use ($locale) {
                                $text = $opt->option_text;
                                if (is_array($text)) {
                                    return $text[$locale] ?? reset($text) ?? '';
                                }
                                return (string) $text;
                            })
                            ->filter()
                            ->implode(', ');
                    })
                    ->wrap()
                    ->limit(80),

                ToggleColumn::make('is_active')
                    ->label('Aktiv')
                    ->sortable()
                    ->disabled(fn ($record) => ! QuestionResource::ownsRecord($record)),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Sual Növü')
                    ->options([
                        'mcq'        => 'Çox Seçimli',
                        'true_false' => 'Doğru/Yanlış',
                    ]),
                SelectFilter::make('difficulty')
                    ->label('Çətinlik')
                    ->options([
                        'easy'   => 'Asan',
                        'medium' => 'Orta',
                        'hard'   => 'Çətin',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Aktiv'),
                TernaryFilter::make('merchant_id')
                    ->label('Mənbə')
                    ->nullable()
                    ->trueLabel('Öz suallarım')
                    ->falseLabel('Hazır baza')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('merchant_id'),
                        false: fn ($query) => $query->whereNull('merchant_id'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn ($record) => QuestionResource::ownsRecord($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Filament::auth()->user()?->is_admin ?? false),
                ]),
            ]);
    }
}
