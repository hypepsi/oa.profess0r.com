<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerBillingStats extends BaseWidget
{
    protected function getStats(): array
    {
        // Get data from the page
        $page = $this->livewire;
        $stats = $page->stats ?? [];
        
        if (empty($stats)) {
            return [
                Stat::make('Expected', '$0.00')
                    ->description('Current month billable')
                    ->descriptionIcon('heroicon-o-banknotes')
                    ->color('gray'),
            ];
        }

        $currentExpected = $stats['current_expected'] ?? 0;
        $currentReceived = $stats['current_received'] ?? 0;
        $waivedTotal = $stats['waived_total'] ?? 0;
        $hasOverdue = $stats['has_overdue'] ?? false;
        $overdueMessage = $stats['overdue_message'] ?? 'All good';

        return [
            Stat::make('Expected', '$' . number_format($currentExpected, 2))
                ->description('Current month billable')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Received', '$' . number_format($currentReceived, 2))
                ->description('Payments confirmed')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color($currentReceived > 0 ? 'success' : 'gray'),

            Stat::make('Waived', '$' . number_format($waivedTotal, 2))
                ->description('Waived amounts')
                ->descriptionIcon('heroicon-o-hand-raised')
                ->color($waivedTotal > 0 ? 'warning' : 'gray'),

            Stat::make('Status', $overdueMessage)
                ->description($hasOverdue ? 'Action required' : 'No overdue payments')
                ->descriptionIcon($hasOverdue ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($hasOverdue ? 'danger' : 'success'),
        ];
    }
}

