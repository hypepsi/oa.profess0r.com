<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ClientStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('New Clients (Last 30 days)', Customer::where('created_at', '>=', Carbon::now()->subDays(30))->count())
                ->description('Clients added in the last 30 days')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),

            Stat::make('Total Clients', Customer::count())
                ->description('All clients in the system')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}
