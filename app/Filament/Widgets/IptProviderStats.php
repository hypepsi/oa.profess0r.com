<?php

namespace App\Filament\Widgets;

use App\Models\IptProvider;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class IptProviderStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total IPT Providers', IptProvider::count())
                ->description('All IPT transit providers in the system')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('primary'),
        ];
    }
}
