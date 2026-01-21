<?php

namespace App\Filament\Resources\GeoFeedLocationResource\Pages;

use App\Filament\Resources\GeoFeedLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGeoFeedLocations extends ListRecords
{
    protected static string $resource = GeoFeedLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
