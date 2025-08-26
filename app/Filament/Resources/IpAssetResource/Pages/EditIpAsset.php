<?php

namespace App\Filament\Resources\IpAssetResource\Pages;

use App\Filament\Resources\IpAssetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIpAsset extends EditRecord
{
    protected static string $resource = IpAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
