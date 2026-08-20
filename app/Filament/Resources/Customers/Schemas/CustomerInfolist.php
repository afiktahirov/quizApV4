<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil')
                    ->schema([
                        TextEntry::make('name')->label('Ad'),
                        TextEntry::make('phone')->label('Telefon'),
                        TextEntry::make('email')->label('Email')->placeholder('-'),
                        TextEntry::make('created_at')->label('Qeydiyyat')->dateTime('d.m.Y H:i')->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make('Abunəlik')
                    ->schema([
                        TextEntry::make('plan.name')
                            ->label('Cari paket')
                            ->badge()
                            ->placeholder('Paket yoxdur')
                            ->color(fn ($state, Customer $record) => $record->hasActiveSubscription() ? 'success' : 'gray'),
                        TextEntry::make('subscription_ends_at')
                            ->label('Bitmə tarixi')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('status_label')
                            ->label('Status')
                            ->state(fn (Customer $record) => $record->hasActiveSubscription() ? 'Aktiv' : 'Passiv')
                            ->badge()
                            ->color(fn (Customer $record) => $record->hasActiveSubscription() ? 'success' : 'danger'),
                        TextEntry::make('auto_renew')
                            ->label('Avtomatik yenilənmə')
                            ->state(fn (Customer $record) => $record->auto_renew ? 'Açıq' : 'Bağlı')
                            ->badge()
                            ->color(fn (Customer $record) => $record->auto_renew ? 'success' : 'gray'),
                        TextEntry::make('usage_quizzes')
                            ->label('Bu gün oynadığı quiz')
                            ->state(fn (Customer $record) => $record->quizzesPlayedToday()
                                . ' / ' . ($record->planLimit('quizzes_per_day') ?? '∞')),
                        TextEntry::make('usage_coupons')
                            ->label('Bu ay qazandığı kupon')
                            ->state(fn (Customer $record) => $record->couponsThisMonth()
                                . ' / ' . ($record->planLimit('coupons_per_month') ?? '∞')),
                    ])
                    ->columns(2),
            ]);
    }
}
