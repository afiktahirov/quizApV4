<?php

namespace App\Filament\Resources\CustomerPlans\Pages;

use App\Filament\Resources\CustomerPlans\CustomerPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerPlans extends ListRecords
{
    protected static string $resource = CustomerPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
