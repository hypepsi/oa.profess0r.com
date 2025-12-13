<?php

namespace App\Filament\Widgets;

use App\Services\BillingCalculator;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BillingOverviewStats extends BaseWidget
{
    protected function getStats(): array
    {
        $current = Carbon::now('Asia/Shanghai')->startOfMonth();
        $summary = BillingCalculator::getOverviewForMonth($current);

        $customersDue = $summary['customers_due'] ?? 0;
        $expectedTotal = $summary['expected_total'] ?? 0;
        $receivedTotal = $summary['received_total'] ?? 0;
        $overdueAmount = $summary['overdue_amount_total'] ?? 0;
        $overdueList = $summary['overdue'] ?? [];
        $overdueCount = count($overdueList);

        return [
            Stat::make('Customers to Bill', number_format($customersDue))
                ->description('Active customers this month')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Expected Revenue', '$' . number_format($expectedTotal, 2))
                ->description('Total billable amount')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Received (Confirmed)', '$' . number_format($receivedTotal, 2))
                ->description('Payments confirmed')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('info'),

            Stat::make('Overdue Amount', '$' . number_format($overdueAmount, 2))
                ->description($overdueCount > 0 ? "{$overdueCount} customer(s) need follow-up" : 'All good')
                ->descriptionIcon($overdueCount > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($overdueCount > 0 ? 'danger' : 'success'),
        ];
    }
}

