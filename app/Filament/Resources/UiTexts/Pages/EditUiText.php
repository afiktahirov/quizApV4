<?php

namespace App\Filament\Resources\UiTexts\Pages;

use App\Filament\Resources\UiTexts\UiTextResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUiText extends EditRecord
{
    protected static string $resource = UiTextResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
