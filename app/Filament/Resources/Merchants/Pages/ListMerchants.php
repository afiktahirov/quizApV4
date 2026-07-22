<?php

namespace App\Filament\Resources\Merchants\Pages;

use App\Filament\Resources\Merchants\MerchantResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListMerchants extends ListRecords
{
    protected static string $resource = MerchantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => Filament::auth()->user()?->is_admin ?? false),
        ];
    }
}
