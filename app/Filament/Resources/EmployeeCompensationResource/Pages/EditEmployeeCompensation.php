<?php

namespace App\Filament\Resources\EmployeeCompensationResource\Pages;

use App\Filament\Resources\EmployeeCompensationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeCompensation extends EditRecord
{
    protected static string $resource = EmployeeCompensationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
