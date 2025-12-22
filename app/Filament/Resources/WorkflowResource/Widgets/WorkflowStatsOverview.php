<?php

namespace App\Filament\Resources\WorkflowResource\Widgets;

use App\Models\Employee;
use App\Models\Workflow;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Carbon;

class WorkflowStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null; // 手动刷新
    protected static bool $isLazy = false; // 避免懒加载丢失路由参数

    public ?int $month = null;
    public ?int $year = null;

    public function mount(): void
    {
        $route = request()->route();
        $monthParam = $route?->parameter('month');
        $yearParam = $route?->parameter('year');

        $this->month = $monthParam !== null ? (int) $monthParam : null;
        $this->year = $yearParam !== null ? (int) $yearParam : null;
    }

    protected function getCards(): array
    {
        $query = Workflow::query();
        
        if ($this->month && $this->year) {
            $startDate = Carbon::createFromDate($this->year, $this->month, 1, 'Asia/Shanghai')->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            
            $query->whereBetween('created_at', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay()
            ]);
        }
        
        // Apply employee filter if not admin (same logic as ListWorkflowsByMonth)
        if (auth()->check() && auth()->user()->email !== 'admin@bunnycommunications.com') {
            $employee = Employee::where('email', auth()->user()->email)->first();
            if ($employee) {
                $query->whereHas('assignees', function ($q) use ($employee) {
                    $q->where('employees.id', $employee->id);
                });
            } else {
                // If no employee record found, return empty results
                $query->whereRaw('1 = 0');
            }
        }
        
        $counts = $query
            ->selectRaw("
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 'updated' THEN 1 ELSE 0 END) as updated_count,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count
            ")
            ->first();

        return [
            Card::make('Open', (int) ($counts->open_count ?? 0))
                ->icon('heroicon-m-clock')
                ->color('info'),
            Card::make('Updated', (int) ($counts->updated_count ?? 0))
                ->icon('heroicon-m-arrow-path')
                ->color('warning'),
            Card::make('Approved', (int) ($counts->approved_count ?? 0))
                ->icon('heroicon-m-check-badge')
                ->color('success'),
            Card::make('Overdue', (int) ($counts->overdue_count ?? 0))
                ->icon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
