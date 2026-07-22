<?php

namespace App\Filament\Resources\Merchants\Tables;

use App\Models\Merchant;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MerchantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ad')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'active' ? 'Aktiv' : 'Bloklu')
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
                TextColumn::make('plan.name')
                    ->label('Paket')
                    ->placeholder('-'),
                TextColumn::make('subscription_ends_at')
                    ->label('Abunəlik bitir')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Limitsiz')
                    ->sortable()
                    ->color(fn ($record) => $record->isSubscribed() ? 'success' : 'danger'),
                TextColumn::make('subscriptions_sum_amount')
                    ->label('Ümumi gəlir')
                    ->sum('subscriptions', 'amount')
                    ->formatStateUsing(fn ($state) => number_format((float) ($state ?? 0), 2) . ' AZN'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                // ---- Abunə təyin et / uzat ----
                Action::make('grantSubscription')
                    ->label('Abunə təyin/uzat')
                    ->icon('heroicon-o-rectangle-stack')
                    ->color('success')
                    ->schema([
                        Select::make('plan_id')
                            ->label('Paket')
                            ->options(Plan::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                            ->required(),
                        TextInput::make('periods')
                            ->label('Neçə dövr (ay/il)')
                            ->numeric()->minValue(1)->default(1)->required()
                            ->helperText('Paketin dövrünə görə (aylıq paket → ay sayı, illik → il sayı)'),
                        Textarea::make('note')
                            ->label('Qeyd (opsional)')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->action(function (Merchant $record, array $data) {
                        $plan = Plan::findOrFail($data['plan_id']);
                        app(SubscriptionService::class)->grant(
                            $record,
                            $plan,
                            (int) $data['periods'],
                            Filament::auth()->user(),
                            $data['note'] ?? null,
                        );

                        Notification::make()
                            ->title('Abunəlik yeniləndi')
                            ->body($record->name . ' → ' . $plan->name . ' (' . $record->fresh()->subscription_ends_at?->format('d.m.Y') . '-dək)')
                            ->success()
                            ->send();
                    }),

                // ---- Blokla / Aç ----
                Action::make('toggleBlock')
                    ->label(fn (Merchant $record) => $record->status === 'active' ? 'Blokla' : 'Bloku aç')
                    ->icon(fn (Merchant $record) => $record->status === 'active' ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (Merchant $record) => $record->status === 'active' ? 'danger' : 'gray')
                    ->requiresConfirmation()
                    ->action(function (Merchant $record) {
                        $svc = app(SubscriptionService::class);
                        if ($record->status === 'active') {
                            $svc->block($record);
                            $msg = 'Mağaza bloklandı';
                        } else {
                            $svc->unblock($record);
                            $msg = 'Blok açıldı';
                        }
                        Notification::make()->title($msg)->success()->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
