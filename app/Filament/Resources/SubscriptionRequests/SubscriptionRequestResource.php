<?php

namespace App\Filament\Resources\SubscriptionRequests;

use App\Filament\Resources\SubscriptionRequests\Pages\ListSubscriptionRequests;
use App\Filament\Resources\SubscriptionRequests\Tables\SubscriptionRequestsTable;
use App\Models\SubscriptionRequest;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Mağazaların "Abunəliyim" səhifəsindən göndərdiyi paket sorğuları.
 * Normal axında ödəniş uğurlu olanda bunlar avtomatik "approved" olur
 * (bax: PaymentService::handleReturn, SubscriptionService::approveViaPayment).
 * Bu resurs əsasən izləmə və manual override (məs. bank xaricində razılaşma) üçündür.
 */
class SubscriptionRequestResource extends Resource
{
    protected static ?string $model = SubscriptionRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Abunəlik sorğuları';

    protected static string|\UnitEnum|null $navigationGroup = 'Abunəlik';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getLabel(): string
    {
        return 'Abunəlik sorğusu';
    }

    public static function getPluralLabel(): string
    {
        return 'Abunəlik sorğuları';
    }

    /** Yalnız super admin görür — mağazalar öz sorğularını "Abunəliyim" səhifəsindən idarə edir. */
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
        return parent::getEloquentQuery()->with(['merchant', 'plan', 'reviewer']);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionRequestsTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptionRequests::route('/'),
        ];
    }
}
