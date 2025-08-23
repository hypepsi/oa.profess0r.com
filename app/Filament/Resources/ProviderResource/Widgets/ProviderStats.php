<?php

namespace App\Filament\Resources\ProviderResource\Widgets;

use App\Models\Provider;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProviderStats extends BaseWidget
{
    protected function getStats(): array
    {
        $total = Provider::count();
        $active = Provider::where('active', true)->count();
        $inactive = Provider::where('active', false)->count();

        return [
            Stat::make('Total Providers', $total)
                ->description('All providers in system')
                ->descriptionIcon('heroicon-o-rectangle-stack')
                ->color('primary'),

            Stat::make('Active Providers', $active)
                ->description('Currently enabled')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Inactive Providers', $inactive)
                ->description('Disabled or inactive')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
