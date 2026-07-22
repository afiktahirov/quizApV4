<?php

namespace App\Filament\Resources\Coupons;

use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Models\Coupon;
use App\Services\CouponService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Kuponlar';

    protected static ?string $recordTitleAttribute = 'code';

    public static function getLabel(): string
    {
        return 'Kupon';
    }

    public static function getPluralLabel(): string
    {
        return 'Kuponlar';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kod')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('merchant.name')
                    ->label('Müəssisə')
                    ->visible(fn () => Filament::auth()->user()?->is_admin ?? false),
                TextColumn::make('session.customer.name')
                    ->label('Müştəri'),
                TextColumn::make('discount_type')
                    ->label('Növ')
                    ->formatStateUsing(fn (string $state) => $state === 'percent' ? 'Faiz' : 'Məbləğ'),
                TextColumn::make('value')
                    ->label('Dəyər')
                    ->formatStateUsing(fn ($state, Coupon $record) => $record->discount_type === 'percent' ? "{$state}%" : "{$state} AZN"),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active'   => 'success',
                        'redeemed' => 'info',
                        'expired'  => 'gray',
                        'revoked'  => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active'   => 'Aktiv',
                        'redeemed' => 'İstifadə olunub',
                        'expired'  => 'Müddəti bitib',
                        'revoked'  => 'Ləğv edilib',
                        default    => $state,
                    }),
                TextColumn::make('expires_at')
                    ->label('Bitmə')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Yaradılıb')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active'   => 'Aktiv',
                        'redeemed' => 'İstifadə olunub',
                        'expired'  => 'Müddəti bitib',
                        'revoked'  => 'Ləğv edilib',
                    ]),
            ])
            ->recordActions([
                Action::make('redeem')
                    ->label('İstifadə et')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Coupon $record) => $record->status === 'active')
                    ->requiresConfirmation()
                    ->schema([
                        Select::make('store_id')
                            ->label('Filial')
                            ->options(function () {
                                $user = Filament::auth()->user();
                                $query = \App\Models\Store::query()->where('status', 'active');
                                if (! $user?->is_admin) {
                                    $query->where('merchant_id', $user?->merchant_id);
                                }
                                return $query->pluck('name', 'id');
                            })
                            ->nullable(),
                        TextInput::make('pos_reference')
                            ->label('POS istinadı (opsional)')
                            ->nullable(),
                    ])
                    ->action(function (Coupon $record, array $data) {
                        try {
                            app(CouponService::class)->redeem(
                                $record,
                                Filament::auth()->user(),
                                $data['store_id'] ?? null,
                                $data['pos_reference'] ?? null
                            );
                            Notification::make()
                                ->title('Kupon istifadə olundu')
                                ->success()
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['merchant', 'session.customer']);
        $user  = Filament::auth()->user();

        if ($user?->is_admin) {
            return $query;
        }

        return $query->where('merchant_id', $user?->merchant_id);
    }

    // Kuponlar yalnız sistem tərəfindən yaradılır
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return Filament::auth()->user()?->is_admin ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoupons::route('/'),
        ];
    }
}
