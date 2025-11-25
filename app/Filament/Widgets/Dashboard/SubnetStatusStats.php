<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\IpAsset;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SubnetStatusStats extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        $totalSubnets = IpAsset::count();
        $activeSubnets = IpAsset::where('status', 'Active')->count();
        // Inactive includes Released, Reserved, or anything not Active
        $inactiveSubnets = IpAsset::where('status', '!=', 'Active')->count();

        return [
            Stat::make('Total Subnets', number_format($totalSubnets))
                ->description('All IP assets in the system')
                ->descriptionIcon('heroicon-o-server-stack')
                ->color('primary'),
            
            Stat::make('Active Subnets', number_format($activeSubnets))
                ->description('Currently active subnets')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            
            Stat::make('Inactive Subnets', number_format($inactiveSubnets))
                ->description('Reserved or Released subnets')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}


