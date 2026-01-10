<?php

namespace App\Filament\Resources\MonthlyPerformanceResource\Pages;

use App\Filament\Resources\MonthlyPerformanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMonthlyPerformances extends ListRecords
{
    protected static string $resource = MonthlyPerformanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
