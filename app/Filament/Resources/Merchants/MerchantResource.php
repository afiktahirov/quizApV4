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

class MerchantResource extends Resource
{
    protected static ?string $model = Merchant::class;

    protected static ?string $recordTitleAttribute = 'merchant';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';


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
            //
        ];
    }

    public static function canViewAny(): bool
    {
        $u = Filament::auth()->user();
        return $u && $u->role === 'super_admin';
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
