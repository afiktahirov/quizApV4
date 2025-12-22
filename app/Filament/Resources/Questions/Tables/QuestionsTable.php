<?php

namespace App\Filament\Resources\Questions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables;
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
                // Sual Başlığı (JSON/translatable üçün aktiv dilə görə göstər)
                TextColumn::make('title')
                    ->label('Sual Başlığı')
                    ->searchable()
                    ->limit(60)
                    ->sortable()
                    ->state(function ($record) {
                        // title JSON-dursa, "az" açarını götür
                        if (is_array($record->title)) {
                            return $record->title['az'] ?? '';
                        }

                        return $record->title;
                    })
                    ->limit(60),

                // Sual Növü (badge + enum)
                BadgeColumn::make('type')
                    ->label('Sual Növü')
                    ->colors([
                        'primary' => 'mcq',
                        'success' => 'true_false',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'mcq' => 'Çoxseçimli',
                        'true_false' => 'Doğru / Yanlış',
                        default => ucfirst($state),
                    })
                    ->sortable(),


                // Variant sayı (hasMany count)
                TextColumn::make('options_count')
                    ->label('Variant sayı')
                    ->counts('options') // Filament v4 relationship count
                    ->sortable(),

                // Düzgün cavab(lar)
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



                // Aktivlik
                ToggleColumn::make('is_active')
                    ->label('Aktiv')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Sual Növü')
                    ->options([
                        'mcq' => 'Çox Seçimli',
                        'true_false' => 'Doğru/Yanlış',
                    ]),
                SelectFilter::make('difficulty')
                    ->label('Çətinlik')
                    ->options([
                        'easy' => 'Asan',
                        'medium' => 'Orta',
                        'hard' => 'Çətin',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Aktiv'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
