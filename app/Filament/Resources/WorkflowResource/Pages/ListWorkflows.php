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
        return auth()->user()->email === 'admin@bunnycommunications.com';
    }
}
