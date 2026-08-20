<?php

namespace App\Filament\Resources\CustomerSubscriptionRequests\Pages;

use App\Filament\Resources\CustomerSubscriptionRequests\CustomerSubscriptionRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerSubscriptionRequests extends ListRecords
{
    protected static string $resource = CustomerSubscriptionRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
