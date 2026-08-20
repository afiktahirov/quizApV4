<?php

namespace App\Filament\Resources\CustomerPlans\Pages;

use App\Filament\Resources\CustomerPlans\CustomerPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerPlan extends EditRecord
{
    protected static string $resource = CustomerPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
