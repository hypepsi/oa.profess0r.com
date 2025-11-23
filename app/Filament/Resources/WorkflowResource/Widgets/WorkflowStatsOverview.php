<?php

namespace App\Filament\Resources\WorkflowResource\Widgets;

use App\Models\Workflow;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class WorkflowStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null; // No auto-refresh, manual page refresh
    protected static bool $isLazy = true;

    protected function getCards(): array
    {
        $counts = Workflow::query()
            ->selectRaw("
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 'updated' THEN 1 ELSE 0 END) as updated_count,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count
            ")
            ->first();

        return [
            Card::make('Open', (int) ($counts->open_count ?? 0))->icon('heroicon-m-clock'),
            Card::make('Updated', (int) ($counts->updated_count ?? 0))->icon('heroicon-m-arrow-path'),
            Card::make('Approved', (int) ($counts->approved_count ?? 0))->icon('heroicon-m-check-badge'),
            Card::make('Overdue', (int) ($counts->overdue_count ?? 0))->icon('heroicon-m-exclamation-triangle'),
        ];
    }
}
