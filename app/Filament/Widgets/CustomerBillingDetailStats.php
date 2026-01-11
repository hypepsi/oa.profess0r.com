<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerBillingDetailStats extends BaseWidget
{
    protected function getStats(): array
    {
        // Get data from the page (parent Livewire component)
        $page = $this->getPage();
        
        if (!$page || !isset($page->snapshot)) {
            return [
                Stat::make('Loading', '...')
                    ->description('Please wait')
                    ->descriptionIcon('heroicon-o-clock')
                    ->color('gray'),
            ];
        }

        $snapshot = $page->snapshot;
        $totalReceived = $page->getTotalReceived();
        $waivedAmount = $page->getWaivedAmount();

        $subnetCount = $snapshot['subnet_count'] ?? 0;
        $subnetTotal = $snapshot['subnet_total'] ?? 0;
        $otherTotal = $snapshot['other_total'] ?? 0;
        $expectedTotal = $snapshot['expected_total'] ?? 0;

        return [
            Stat::make('Expected', '$' . number_format($expectedTotal, 2))
                ->description($waivedAmount > 0 ? '-$' . number_format($waivedAmount, 2) . ' waived' : 'Total billable')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Received', '$' . number_format($totalReceived, 2))
                ->description($totalReceived > 0 ? 'Payments confirmed' : 'No payments yet')
                ->descriptionIcon($totalReceived > 0 ? 'heroicon-o-check-badge' : 'heroicon-o-clock')
                ->color($totalReceived > 0 ? 'success' : 'gray'),

            Stat::make('Subnets', number_format($subnetCount))
                ->description('$' . number_format($subnetTotal, 2))
                ->descriptionIcon('heroicon-o-rectangle-stack')
                ->color('info'),

            Stat::make('Add-ons', '$' . number_format($otherTotal, 2))
                ->description('Extra charges')
                ->descriptionIcon('heroicon-o-plus-circle')
                ->color('info'),
        ];
    }

    protected function getPage()
    {
        return $this->livewire;
    }
}

