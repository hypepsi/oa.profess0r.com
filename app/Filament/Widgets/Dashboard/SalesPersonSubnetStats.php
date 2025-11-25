<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesPersonSubnetStats extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $salesEmployees = Employee::where('department', 'sales')
            ->where('is_active', true)
            ->withCount('ipAssets')
            ->get();

        $stats = [];

        foreach ($salesEmployees as $employee) {
            $stats[] = Stat::make($employee->name, number_format($employee->ip_assets_count))
                ->description('Subnets sold')
                ->descriptionIcon('heroicon-o-user')
                ->color('info');
        }

        if (empty($stats)) {
            $stats[] = Stat::make('No Sales Staff', '0')
                ->color('gray');
        }

        return $stats;
    }
}

