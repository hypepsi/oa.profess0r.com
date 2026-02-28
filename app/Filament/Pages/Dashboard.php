<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\Dashboard\SubnetStatusStats;
use App\Filament\Widgets\Dashboard\RevenueStatusStats;
use App\Filament\Widgets\Dashboard\SalesPersonSubnetStats;
use App\Filament\Widgets\Dashboard\SalesPersonRevenueStats;
use App\Filament\Widgets\Dashboard\BackupStatusWidget;

class Dashboard extends BaseDashboard
{
    public static function canAccess(): bool
    {
        // Allow all authenticated users to hit this route;
        // non-admins are redirected in mount() before any widgets load.
        return auth()->check();
    }

    public function mount(): void
    {
        if (!auth()->user()?->isAdmin()) {
            $now = \Illuminate\Support\Carbon::now('Asia/Shanghai');
            $this->redirect("/admin/workflows/month/{$now->year}/{$now->month}");
            return;
        }
    }

    public function getWidgets(): array
    {
        return [
            SubnetStatusStats::class,
            RevenueStatusStats::class,
            SalesPersonSubnetStats::class,
            SalesPersonRevenueStats::class,
            BackupStatusWidget::class,
        ];
    }
}