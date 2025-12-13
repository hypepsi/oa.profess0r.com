<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class CustomerBillingStats extends BaseWidget
{
    #[Reactive]
    public ?array $stats = null;

    protected function getStats(): array
    {
        $stats = $this->stats ?? [];
        
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
        $hasOverdue = $stats['has_overdue'] ?? false;
        $overdueMessage = $stats['overdue_message'] ?? 'All good';

        return [
            Stat::make('Expected', '$' . number_format($currentExpected, 2))
                ->description('Current month billable')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Confirmed Received', '$' . number_format($currentReceived, 2))
                ->description('Payments confirmed')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('info'),

            Stat::make('Overdue Alert', $overdueMessage)
                ->description($hasOverdue ? 'Action required' : 'No overdue payments')
                ->descriptionIcon($hasOverdue ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($hasOverdue ? 'danger' : 'success'),
        ];
    }
}

