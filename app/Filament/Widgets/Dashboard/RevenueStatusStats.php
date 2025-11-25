<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\IpAsset;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStatusStats extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $totalSales = IpAsset::sum('price');
        $activeSales = IpAsset::where('status', 'Active')->sum('price');
        $inactiveSales = IpAsset::where('status', '!=', 'Active')->sum('price');

        return [
            Stat::make('Total Sales Revenue', '$' . number_format($totalSales, 2))
                ->description('Sum of all IP asset prices')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('primary'),

            Stat::make('Active Revenue', '$' . number_format($activeSales, 2))
                ->description('Revenue from active subnets')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Inactive Revenue', '$' . number_format($inactiveSales, 2))
                ->description('Revenue from inactive subnets')
                ->descriptionIcon('heroicon-o-archive-box')
                ->color('gray'),
        ];
    }
}

