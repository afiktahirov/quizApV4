<?php

namespace App\Filament\Resources\CustomerSubscriptionRequests\Tables;

use App\Models\CustomerSubscriptionRequest;
use App\Services\CustomerSubscriptionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomerSubscriptionRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('İstifadəçi')
                    ->description(fn (CustomerSubscriptionRequest $r) => $r->customer?->phone)
                    ->searchable(),
                TextColumn::make('plan.name')
                    ->label('Paket')
                    ->placeholder('-'),
                TextColumn::make('periods')
                    ->label('Dövr'),
                TextColumn::make('amount')
                    ->label('Məbləğ')
                    ->formatStateUsing(fn ($state, CustomerSubscriptionRequest $r) => number_format((float) $state, 2) . ' ' . $r->currency),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending'   => 'Baxılır',
                        'approved'  => 'Təsdiqlənib',
                        'rejected'  => 'Rədd edilib',
                        'cancelled' => 'Ləğv edilib',
                        default     => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'pending'   => 'warning',
                        'approved'  => 'success',
                        'rejected', 'cancelled' => 'danger',
                        default     => 'gray',
                    }),
                TextColumn::make('payment_status')
                    ->label('Ödəniş')
                    ->state(fn (CustomerSubscriptionRequest $record) => $record->payments()->latest()->value('status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'paid'      => 'Ödənilib',
                        'pending'   => 'Gözlənilir',
                        'failed'    => 'Uğursuz',
                        'refunded'  => 'Geri qaytarılıb',
                        'reversed'  => 'Ləğv edilib',
                        'expired'   => 'Vaxtı bitib',
                        default     => 'Manual',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'paid'              => 'success',
                        'pending'           => 'warning',
                        'failed', 'expired' => 'danger',
                        default             => 'gray',
                    }),
                TextColumn::make('created_at')->label('Tarix')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'   => 'Baxılır',
                        'approved'  => 'Təsdiqlənib',
                        'rejected'  => 'Rədd edilib',
                        'cancelled' => 'Ləğv edilib',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('approve')
                    ->label('Təsdiqlə')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CustomerSubscriptionRequest $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->modalDescription('Abunəlik ödəniş olmadan aktivləşdiriləcək.')
                    ->action(function (CustomerSubscriptionRequest $record) {
                        app(CustomerSubscriptionService::class)->approve($record, Filament::auth()->user());

                        Notification::make()
                            ->title('Abunəlik təsdiqləndi')
                            ->body($record->customer?->name . ' → ' . $record->plan?->name)
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Rədd et')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (CustomerSubscriptionRequest $record) => $record->isPending())
                    ->schema([
                        Textarea::make('note')
                            ->label('Səbəb (opsional)')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->action(function (CustomerSubscriptionRequest $record, array $data) {
                        app(CustomerSubscriptionService::class)->reject($record, Filament::auth()->user(), $data['note'] ?? null);

                        Notification::make()->title('Sorğu rədd edildi')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
