<?php

namespace App\Filament\Widgets;

use App\Models\ActivityLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ActivityLogStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $total   = ActivityLog::count();
        $today   = ActivityLog::whereDate('created_at', today())->count();
        $week    = ActivityLog::where('created_at', '>=', now()->startOfWeek())->count();
        $oldestTs = ActivityLog::oldest('created_at')->value('created_at');

        $oldestLabel = $oldestTs
            ? Carbon::parse($oldestTs)->setTimezone('Asia/Shanghai')->format('Y-m-d')
            : '—';

        return [
            Stat::make('Total Logs', number_format($total))
                ->description('All-time entries in database')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('gray'),

            Stat::make('Today', number_format($today))
                ->description('Events recorded today')
                ->descriptionIcon('heroicon-m-sun')
                ->color('info'),

            Stat::make('This Week', number_format($week))
                ->description('Events this week')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Oldest Entry', $oldestLabel)
                ->description('Earliest log date on record')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
