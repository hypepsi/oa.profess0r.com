<?php

namespace App\Filament\Resources\IncomeOtherItemResource\Pages;

use App\Filament\Resources\IncomeOtherItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIncomeOtherItem extends EditRecord
{
    protected static string $resource = IncomeOtherItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}



