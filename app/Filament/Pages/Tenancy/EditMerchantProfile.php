<?php

namespace App\Filament\Pages\Tenancy;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;

class EditMerchantProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Edit Merchant Profile';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name') // Merchant adını düzəliş etmək
                ->required(),
                TextInput::make('slug') // Slug-u düzəliş etmək
                ->required(),
            ]);
    }
}
