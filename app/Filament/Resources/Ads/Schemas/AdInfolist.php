<?php

namespace App\Filament\Resources\Ads\Schemas;

use App\Models\Ad;
use App\Support\Translatable;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AdInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')
                    ->label('Başlıq')
                    ->state(fn (Ad $record) => Translatable::text($record->title, app()->getLocale())),

                TextEntry::make('merchant.name')
                    ->label('Müəssisə')
                    ->placeholder('Bütün tətbiq'),

                ImageEntry::make('image_path')
                    ->label('Şəkil')
                    ->disk('public')
                    ->placeholder('-'),

                TextEntry::make('content')
                    ->label('Məzmun')
                    ->html()
                    ->state(fn (Ad $record) => Translatable::text($record->content, app()->getLocale()))
                    ->placeholder('-')
                    ->columnSpanFull(),

                TextEntry::make('status')->label('Status'),

                TextEntry::make('starts_at')
                    ->label('Başlama')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Dərhal'),

                TextEntry::make('ends_at')
                    ->label('Bitmə')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Müddətsiz'),

                TextEntry::make('created_at')
                    ->label('Yaradılıb')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-'),
            ]);
    }
}
