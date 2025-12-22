<?php

namespace App\Filament\Widgets;

use App\Services\ExpenseCalculator;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExpenseOverviewStats extends BaseWidget
{
    protected function getStats(): array
    {
        $current = Carbon::now('Asia/Shanghai')->startOfMonth();
        $summary = ExpenseCalculator::getOverviewForMonth($current);

        $providersDue = $summary['providers_due'] ?? 0;
        $expectedTotal = $summary['expected_total'] ?? 0;
        $paidTotal = $summary['paid_total'] ?? 0;
        $overdueAmount = $summary['overdue_amount_total'] ?? 0;
        $overdueList = $summary['overdue'] ?? [];
        $overdueCount = count($overdueList);

        return [
            Stat::make('Providers to Pay', number_format($providersDue))
                ->description('Active providers this month')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('primary'),

            Stat::make('Expected Expense', '$' . number_format($expectedTotal, 2))
                ->description('Total payable amount')
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->color('warning'),

            Stat::make('Paid (Confirmed)', '$' . number_format($paidTotal, 2))
                ->description('Payments confirmed')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success')
                ->extraAttributes(['style' => 'font-weight: 700;']),

            Stat::make('Overdue Amount', '$' . number_format($overdueAmount, 2))
                ->description($overdueCount > 0 ? "{$overdueCount} provider(s) need follow-up" : 'All good')
                ->descriptionIcon($overdueCount > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($overdueCount > 0 ? 'danger' : 'success'),
        ];
    }
}

