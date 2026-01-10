<?php

namespace App\Filament\Resources\MonthlyPerformanceResource\Pages;

use App\Filament\Resources\MonthlyPerformanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonthlyPerformance extends EditRecord
{
    protected static string $resource = MonthlyPerformanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
