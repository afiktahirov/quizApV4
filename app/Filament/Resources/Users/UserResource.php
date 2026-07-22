<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    use \App\Filament\Concerns\RequiresActivePlan;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Müəssisə';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getLabel(): string
    {
        return 'İstifadəçi';
    }

    public static function getPluralLabel(): string
    {
        return 'İstifadəçilər';
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * Super admin bütün istifadəçiləri, merchant_admin öz komandasını
     * (məs. kassirlərini) idarə edir.
     */
    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return (bool) ($user?->is_admin || $user?->isMerchantAdmin()) && static::merchantHasSelectedPlan();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user  = Filament::auth()->user();

        if ($user?->is_admin) {
            return $query;
        }

        return $query->where('merchant_id', $user?->merchant_id);
    }

    public static function canDelete($record): bool
    {
        $user = Filament::auth()->user();

        // özünü silmək olmaz; merchant_admin yalnız öz kassirlərini silə bilər
        if ($record->id === $user?->id) {
            return false;
        }

        return $user?->is_admin
            || ($user?->isMerchantAdmin()
                && $record->merchant_id === $user->merchant_id
                && $record->role === User::ROLE_CASHIER);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view'   => ViewUser::route('/{record}'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }
}
