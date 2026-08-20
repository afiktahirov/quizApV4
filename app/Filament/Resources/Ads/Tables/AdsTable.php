<?php

namespace App\Filament\Resources\Ads\Tables;

use App\Models\Ad;
use App\Support\Translatable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Şəkil')
                    ->disk('public'),

                TextColumn::make('title')
                    ->label('Başlıq')
                    // Başlıq çoxdilli JSON-dur — massiv kimi render olunmasın deyə state()
                    ->state(fn (Ad $record) => Translatable::text($record->title, app()->getLocale()))
                    ->searchable()
                    ->limit(50),

                TextColumn::make('merchant.name')
                    ->label('Müəssisə')
                    ->placeholder('Bütün tətbiq')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'active' ? 'Aktiv' : 'Deaktiv')
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),

                TextColumn::make('visible')
                    ->label('Göstərilir?')
                    ->badge()
                    ->state(fn (Ad $record) => Ad::query()->visible()->whereKey($record->id)->exists() ? 'Bəli' : 'Xeyr')
                    ->color(fn (string $state) => $state === 'Bəli' ? 'success' : 'danger')
                    ->tooltip('Status aktiv + tarix aralığına düşürsə tətbiqdə görünür'),

                TextColumn::make('starts_at')
                    ->label('Başlama')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Dərhal')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Bitmə')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Müddətsiz')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Yaradılıb')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(['active' => 'Aktiv', 'inactive' => 'Deaktiv']),
            ])
            ->defaultSort('created_at', 'desc')
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
