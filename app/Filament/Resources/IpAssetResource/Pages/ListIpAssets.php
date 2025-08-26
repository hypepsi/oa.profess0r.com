<?php

namespace App\Filament\Resources\IpAssetResource\Pages;

use App\Filament\Resources\IpAssetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIpAssets extends ListRecords
{
    protected static string $resource = IpAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
