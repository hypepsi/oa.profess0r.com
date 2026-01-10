<?php

namespace App\Filament\Resources\EmployeeCompensationResource\Pages;

use App\Filament\Resources\EmployeeCompensationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeCompensation extends ListRecords
{
    protected static string $resource = EmployeeCompensationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
