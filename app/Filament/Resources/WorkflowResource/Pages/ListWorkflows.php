<?php

namespace App\Filament\Resources\WorkflowResource\Pages;

use App\Filament\Resources\WorkflowResource;
use App\Filament\Resources\WorkflowResource\Widgets\WorkflowStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

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
}
