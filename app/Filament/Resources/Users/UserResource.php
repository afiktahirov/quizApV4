<?php

namespace App\Filament\Resources\Users;

use Filament\Facades\Filament;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Hexters\HexaLite\HasHexaLite;

class UserResource extends Resource
{

    protected static ?string $model = User::class;

    protected static bool $isScopedToTenant = false;



    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';


    protected static ?string $recordTitleAttribute = 'User';



    public static function getLabel(): string
    {
        return 'İstifadəçi';
    }

    public static function getPluralLabel(): string
    {
        return 'İştifadəçilər';
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
