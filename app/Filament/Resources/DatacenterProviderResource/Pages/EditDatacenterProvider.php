<?php

namespace App\Filament\Resources\DatacenterProviderResource\Pages;

use App\Filament\Resources\DatacenterProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDatacenterProvider extends EditRecord
{
    protected static string $resource = DatacenterProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
