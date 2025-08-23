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

        return [
            Stat::make('Total Providers', $total)
                ->description('All providers in the system')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('primary'),
        ];
    }
}
