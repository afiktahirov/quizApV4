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
                TextEntry::make('customer.name')->label('Müştəri'),
                TextEntry::make('customer.phone')->label('Telefon'),
                TextEntry::make('quiz.title')->label('Kampaniya'),
                TextEntry::make('store.name')->label('Filial')->placeholder('-'),
                TextEntry::make('started_at')->label('Başlama')->dateTime('d.m.Y H:i'),
                TextEntry::make('finished_at')->label('Bitmə')->dateTime('d.m.Y H:i')->placeholder('-'),
                TextEntry::make('score_pct')->label('Bal %')->numeric()->placeholder('-'),
                IconEntry::make('is_passed')->label('Keçib?')->boolean(),
                TextEntry::make('coupon.code')->label('Kupon')->placeholder('-'),
                TextEntry::make('ip')->label('IP')->placeholder('-'),
                TextEntry::make('device_fingerprint')->label('Cihaz izi')->placeholder('-'),
                TextEntry::make('channel')->label('Kanal'),
            ]);
    }
}
