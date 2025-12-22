<?php

namespace App\Filament\Resources\QuestionCategories\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class QuestionCategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')->label('Ad'),
                TextEntry::make('slug')->label('Slug'),
                TextEntry::make('status'),
                TextEntry::make('created_at')->label('Yaradılma tarixi')
                    ->dateTime(),
            ]);
    }
}
