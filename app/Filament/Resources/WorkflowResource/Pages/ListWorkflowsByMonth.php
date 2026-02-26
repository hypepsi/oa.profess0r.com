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
            Actions\CreateAction::make(),
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
        
        // If not admin, show workflows assigned to OR created by current user
        if (!$this->isAdmin()) {
            $userId = auth()->id();
            $employee = Employee::where('email', auth()->user()->email)->first();
            $query->where(function ($q) use ($userId, $employee) {
                $q->where('created_by_user_id', $userId);
                if ($employee) {
                    $q->orWhereHas('assignees', function ($q2) use ($employee) {
                        $q2->where('employees.id', $employee->id);
                    });
                }
            });
        }
        
        return $query;
    }

    protected function isAdmin(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}

