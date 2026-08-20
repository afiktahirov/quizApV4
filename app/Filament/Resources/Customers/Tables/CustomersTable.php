<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Customer;
use App\Models\CustomerPlan;
use App\Services\CustomerSubscriptionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ad')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('plan.name')
                    ->label('Paket')
                    ->badge()
                    ->placeholder('Yoxdur')
                    ->color(fn (?string $state, Customer $r) => $r->hasActiveSubscription() ? 'success' : 'gray'),

                TextColumn::make('subscription_ends_at')
                    ->label('Bitmə tarixi')
                    ->dateTime('d.m.Y')
                    ->placeholder('-')
                    ->description(fn (Customer $r) => $r->hasActiveSubscription()
                        ? $r->daysLeft() . ' gün qalıb'
                        : ($r->subscription_ends_at ? 'Müddəti bitib' : null))
                    ->sortable(),

                TextColumn::make('auto_renew')
                    ->label('Avto-yenilənmə')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Açıq' : 'Bağlı')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Qeydiyyat')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('customer_plan_id')
                    ->label('Paket')
                    ->relationship('plan', 'name'),

                Filter::make('active_subscription')
                    ->label('Yalnız aktiv abunələr')
                    ->query(fn (Builder $q) => $q->subscribed()),

                Filter::make('expired_subscription')
                    ->label('Abunəliyi bitmişlər')
                    ->query(fn (Builder $q) => $q->whereNotNull('subscription_ends_at')
                        ->where('subscription_ends_at', '<', now())),
            ])
            ->recordActions([
                Action::make('grantSubscription')
                    ->label('Abunəlik ver')
                    ->icon('heroicon-o-gift')
                    ->color('success')
                    ->visible(fn () => Filament::auth()->user()?->is_admin ?? false)
                    ->schema([
                        Select::make('customer_plan_id')
                            ->label('Paket')
                            ->options(CustomerPlan::query()->orderBy('sort_order')->pluck('name', 'id'))
                            ->required()
                            ->native(false),
                        TextInput::make('periods')
                            ->label('Neçə dövr')
                            ->numeric()->minValue(1)->default(1)->required()
                            ->helperText('Aylıq paket → ay sayı, illik → il sayı.'),
                        TextInput::make('note')
                            ->label('Qeyd')
                            ->placeholder('məs. kompensasiya, kampaniya hədiyyəsi')
                            ->nullable(),
                    ])
                    ->action(function (Customer $record, array $data) {
                        $plan = CustomerPlan::findOrFail($data['customer_plan_id']);

                        app(CustomerSubscriptionService::class)->grant(
                            $record,
                            $plan,
                            (int) $data['periods'],
                            Filament::auth()->user(),
                            $data['note'] ?? 'Admin tərəfindən əl ilə verildi',
                        );

                        Notification::make()
                            ->title('Abunəlik verildi')
                            ->body($record->name . ' → ' . $plan->name)
                            ->success()
                            ->send();
                    }),
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
