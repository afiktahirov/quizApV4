<?php

namespace App\Filament\Resources\CustomerSubscriptionRequests;

use App\Filament\Resources\CustomerSubscriptionRequests\Pages\ListCustomerSubscriptionRequests;
use App\Filament\Resources\CustomerSubscriptionRequests\Tables\CustomerSubscriptionRequestsTable;
use App\Models\CustomerSubscriptionRequest;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * İstifadəçilərin paket sorğuları. Onlayn ödəniş uğurlu olanda avtomatik
 * "approved" olurlar; bu resurs izləmə və manual təsdiq (bank xaricində
 * razılaşma, kompensasiya və s.) üçündür.
 */
class CustomerSubscriptionRequestResource extends Resource
{
    protected static ?string $model = CustomerSubscriptionRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'İstifadəçi sorğuları';

    protected static string|\UnitEnum|null $navigationGroup = 'Abunəlik';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getLabel(): string
    {
        return 'İstifadəçi abunəlik sorğusu';
    }

    public static function getPluralLabel(): string
    {
        return 'İstifadəçi sorğuları';
    }

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->is_admin ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['customer', 'plan', 'reviewer']);
    }

    public static function table(Table $table): Table
    {
        return CustomerSubscriptionRequestsTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerSubscriptionRequests::route('/'),
        ];
    }
}
