<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\Dashboard\SubnetStatusStats;
use App\Filament\Widgets\Dashboard\RevenueStatusStats;
use App\Filament\Widgets\Dashboard\SalesPersonSubnetStats;
use App\Filament\Widgets\Dashboard\SalesPersonRevenueStats;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            SubnetStatusStats::class,
            RevenueStatusStats::class,
            SalesPersonSubnetStats::class,
            SalesPersonRevenueStats::class,
        ];
    }
}


