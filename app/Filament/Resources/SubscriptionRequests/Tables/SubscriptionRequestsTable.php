<?php

namespace App\Filament\Resources\SubscriptionRequests\Tables;

use App\Models\SubscriptionRequest;
use App\Services\SubscriptionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('merchant.name')
                    ->label('Mağaza')
                    ->searchable(),
                TextColumn::make('plan.name')
                    ->label('Paket')
                    ->placeholder('-'),
                TextColumn::make('periods')
                    ->label('Dövr'),
                TextColumn::make('amount')
                    ->label('Məbləğ')
                    ->formatStateUsing(fn ($state, SubscriptionRequest $r) => number_format((float) $state, 2) . ' ' . $r->currency),
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
                    ->state(fn (SubscriptionRequest $record) => $record->payments()->latest()->value('status'))
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
                        'paid'                => 'success',
                        'pending'             => 'warning',
                        'failed', 'expired'   => 'danger',
                        default               => 'gray',
                    }),
                TextColumn::make('reviewer.name')->label('Baxan')->placeholder('-'),
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
                    ->visible(fn (SubscriptionRequest $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->action(function (SubscriptionRequest $record) {
                        app(SubscriptionService::class)->approve($record, Filament::auth()->user());

                        Notification::make()
                            ->title('Abunəlik təsdiqləndi')
                            ->body($record->merchant->name . ' → ' . $record->plan?->name)
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Rədd et')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (SubscriptionRequest $record) => $record->isPending())
                    ->schema([
                        Textarea::make('note')
                            ->label('Səbəb (opsional)')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->action(function (SubscriptionRequest $record, array $data) {
                        app(SubscriptionService::class)->reject($record, Filament::auth()->user(), $data['note'] ?? null);

                        Notification::make()
                            ->title('Sorğu rədd edildi')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
