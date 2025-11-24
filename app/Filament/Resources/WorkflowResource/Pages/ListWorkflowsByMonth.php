<?php

namespace App\Filament\Resources\WorkflowResource\Pages;

use App\Filament\Resources\WorkflowResource;
use App\Filament\Resources\WorkflowResource\Widgets\WorkflowStatsOverview;
use App\Models\Employee;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ListWorkflowsByMonth extends ListRecords
{
    protected static string $resource = WorkflowResource::class;

    public ?string $month = null;
    public ?string $year = null;

    public function mount(): void
    {
        // Get month and year from route parameters
        $this->month = request()->route('month');
        $this->year = request()->route('year');
        
        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        $isAdmin = $this->isAdmin();
        
        return [
            Actions\CreateAction::make()
                ->visible(fn () => $isAdmin),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            WorkflowStatsOverview::class,
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        // Filter by month and year
        if ($this->month && $this->year) {
            $startDate = Carbon::createFromDate((int)$this->year, (int)$this->month, 1)
                ->setTimezone('Asia/Shanghai')
                ->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            
            $query->whereBetween('created_at', [
                $startDate->startOfDay(),
                $endDate->endOfDay()
            ]);
        }
        
        // If not admin, only show workflows assigned to current user
        if (!$this->isAdmin()) {
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
        
        return $query;
    }

    protected function isAdmin(): bool
    {
        return auth()->user()->email === 'admin@bunnycommunications.com';
    }
}

