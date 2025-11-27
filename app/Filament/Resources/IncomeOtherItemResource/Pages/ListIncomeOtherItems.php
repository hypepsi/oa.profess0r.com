<?php

namespace App\Filament\Resources\IncomeOtherItemResource\Pages;

use App\Filament\Resources\IncomeOtherItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIncomeOtherItems extends ListRecords
{
    protected static string $resource = IncomeOtherItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}



