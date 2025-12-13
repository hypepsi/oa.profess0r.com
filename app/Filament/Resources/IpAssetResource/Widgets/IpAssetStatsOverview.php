<?php

namespace App\Filament\Resources\IpAssetResource\Widgets;

use App\Models\IpAsset;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class IpAssetStatsOverview extends BaseWidget
{
    protected function getCards(): array
    {
        $total = IpAsset::count();
        $active = IpAsset::where('status', 'Active')->count();
        $reserved = IpAsset::where('status', 'Reserved')->count();
        $released = IpAsset::where('status', 'Released')->count();
        
        $totalCost = IpAsset::sum('cost') ?? 0;
        $totalPrice = IpAsset::sum('price') ?? 0;

        return [
            Card::make('Total IP Assets', $total)
                ->icon('heroicon-o-server-stack')
                ->color('primary')
                ->description('All IP assets in the system'),
            
            Card::make('Active', $active)
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Currently active'),
            
            Card::make('Reserved', $reserved)
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->description('Reserved for future use'),
            
            Card::make('Released', $released)
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->description('Released assets'),
            
            Card::make('Total Cost', '$' . number_format($totalCost, 2))
                ->icon('heroicon-o-currency-dollar')
                ->color('info')
                ->description('Sum of all costs'),
            
            Card::make('Total Price', '$' . number_format($totalPrice, 2))
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->description('Sum of all prices'),
        ];
    }
}

