<?php

namespace App\Filament\Resources\BillingOtherItemResource\Pages;

use App\Filament\Resources\BillingOtherItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBillingOtherItems extends ListRecords
{
    protected static string $resource = BillingOtherItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
