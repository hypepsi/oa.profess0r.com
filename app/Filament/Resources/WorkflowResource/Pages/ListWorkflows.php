<?php

namespace App\Filament\Resources\WorkflowResource\Pages;

use App\Filament\Resources\WorkflowResource;
use App\Filament\Resources\WorkflowResource\Widgets\WorkflowStatsOverview;
use App\Models\Employee;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListWorkflows extends ListRecords
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
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

    public function getDefaultTableGrouping(): ?string
    {
        return 'created_at';
    }

    public function getDefaultTableSortColumn(): ?string
    {
        return 'created_at';
    }

    public function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    protected function isAdmin(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
