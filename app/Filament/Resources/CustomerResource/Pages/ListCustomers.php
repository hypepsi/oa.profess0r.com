<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\CustomerResource\Widgets\ClientStats;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderWidgets(): array
    {
        // 只保留统计卡片，不加载图表部件
        return [
            ClientStats::class,
        ];
    }
}
