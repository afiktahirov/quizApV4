<?php

namespace App\Filament\Resources\CustomerPlans\Pages;

use App\Filament\Resources\CustomerPlans\CustomerPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerPlan extends CreateRecord
{
    protected static string $resource = CustomerPlanResource::class;
}
