<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class CustomerBillingDetailStats extends BaseWidget
{
    #[Reactive]
    public ?array $snapshot = null;
    
    #[Reactive]
    public ?float $totalReceived = null;
    
    #[Reactive]
    public ?float $waivedAmount = null;

    protected function getStats(): array
    {
        if (!$this->snapshot) {
            return [
                Stat::make('Loading', '...')
                    ->description('Please wait')
                    ->descriptionIcon('heroicon-o-clock')
                    ->color('gray'),
            ];
        }

        $subnetCount = $this->snapshot['subnet_count'] ?? 0;
        $subnetTotal = $this->snapshot['subnet_total'] ?? 0;
        $otherTotal = $this->snapshot['other_total'] ?? 0;
        $expectedTotal = $this->snapshot['expected_total'] ?? 0;
        $totalReceived = $this->totalReceived ?? 0;
        $waivedAmount = $this->waivedAmount ?? 0;

        return [
            Stat::make('Subnets', number_format($subnetCount))
                ->description('$' . number_format($subnetTotal, 2))
                ->descriptionIcon('heroicon-o-rectangle-stack')
                ->color('primary'),

            Stat::make('Add-ons', '$' . number_format($otherTotal, 2))
                ->description('Extra charges')
                ->descriptionIcon('heroicon-o-plus-circle')
                ->color('info'),

            Stat::make('Expected Total', '$' . number_format($expectedTotal, 2))
                ->description($waivedAmount > 0 ? '-$' . number_format($waivedAmount, 2) . ' waived' : 'Total billable')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('warning'),

            Stat::make('Total Received', '$' . number_format($totalReceived, 2))
                ->description($totalReceived > 0 ? 'Payments confirmed' : 'No payments yet')
                ->descriptionIcon($totalReceived > 0 ? 'heroicon-o-check-badge' : 'heroicon-o-clock')
                ->color($totalReceived > 0 ? 'success' : 'gray'),
        ];
    }
}

