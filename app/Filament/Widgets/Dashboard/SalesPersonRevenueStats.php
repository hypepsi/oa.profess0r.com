<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class SalesPersonRevenueStats extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $salesEmployees = Employee::where('department', 'sales')
            ->where('is_active', true)
            ->withSum('ipAssets', 'price')
            ->get();

        $stats = [];

        foreach ($salesEmployees as $employee) {
            $revenue = $employee->ip_assets_sum_price ?? 0;
            $stats[] = Stat::make($employee->name . ' Revenue', '$' . number_format($revenue, 2))
                ->description('Total Sales Revenue')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('warning');
        }

        if (empty($stats)) {
            $stats[] = Stat::make('No Sales Revenue', '$0.00')
                ->color('gray');
        }

        return $stats;
    }
}

