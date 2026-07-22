<?php

namespace App\Filament\Resources\SubscriptionRequests\Pages;

use App\Filament\Resources\SubscriptionRequests\SubscriptionRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionRequests extends ListRecords
{
    protected static string $resource = SubscriptionRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
