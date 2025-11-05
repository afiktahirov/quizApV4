<?php

namespace App\Filament\Resources\QuizSessions\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class QuizSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('merchant_id')
                    ->numeric(),
                TextEntry::make('store_id')
                    ->numeric(),
                TextEntry::make('quiz_id')
                    ->numeric(),
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('started_at')
                    ->dateTime(),
                TextEntry::make('finished_at')
                    ->dateTime(),
                TextEntry::make('score_pct')
                    ->numeric(),
                IconEntry::make('is_passed')
                    ->boolean(),
                TextEntry::make('ip'),
                TextEntry::make('device_fingerprint'),
                TextEntry::make('channel'),
            ]);
    }
}
