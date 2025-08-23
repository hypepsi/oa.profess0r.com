<?php

namespace App\Filament\Resources\IptProviderResource\Pages;

use App\Filament\Resources\IptProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIptProvider extends EditRecord
{
    protected static string $resource = IptProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
