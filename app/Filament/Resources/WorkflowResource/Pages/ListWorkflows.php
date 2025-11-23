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
        
        // 如果不是管理员，只显示分配给当前用户的 workflow
        if (!$this->isAdmin()) {
            $employee = Employee::where('email', auth()->user()->email)->first();
            if ($employee) {
                $query->whereHas('assignees', function ($q) use ($employee) {
                    $q->where('employees.id', $employee->id);
                });
            } else {
                // 如果没有找到对应的员工记录，返回空结果
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
