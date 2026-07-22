<?php

namespace App\Filament\Resources\Merchants;

use Filament\Facades\Filament;
use App\Filament\Resources\Merchants\Pages\CreateMerchant;
use App\Filament\Resources\Merchants\Pages\EditMerchant;
use App\Filament\Resources\Merchants\Pages\ListMerchants;
use App\Filament\Resources\Merchants\Schemas\MerchantForm;
use App\Filament\Resources\Merchants\Tables\MerchantsTable;
use App\Models\Merchant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MerchantResource extends Resource
{
    protected static ?string $model = Merchant::class;

    protected static ?string $recordTitleAttribute = 'merchant';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|\UnitEnum|null $navigationGroup = 'Müəssisə';

    protected static ?int $navigationSort = 1;


    public static function getLabel(): string
    {
        return 'Mağaza';
    }

    public static function getPluralLabel(): string
    {
        return 'Mağazalar';
    }

    public static function form(Schema $schema): Schema
    {
        return MerchantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MerchantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Merchants\RelationManagers\SubscriptionsRelationManager::class,
        ];
    }

    /** Super admin hamısını, merchant_admin yalnız öz mağazasını idarə edir. */
    public static function canViewAny(): bool
    {
        $u = Filament::auth()->user();
        return $u && in_array($u->role, ['super_admin', 'merchant_admin'], true);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = Filament::auth()->user();

        if ($user?->is_admin) {
            return $query;
        }

        return $query->where('id', $user?->merchant_id);
    }

    // Yeni mağaza yalnız super admin tərəfindən yaradılır/silinir
    public static function canCreate(): bool
    {
        return Filament::auth()->user()?->is_admin ?? false;
    }

    public static function canDelete($record): bool
    {
        return Filament::auth()->user()?->is_admin ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMerchants::route('/'),
            'create' => CreateMerchant::route('/create'),
            'edit' => EditMerchant::route('/{record}/edit'),
        ];
    }
}
