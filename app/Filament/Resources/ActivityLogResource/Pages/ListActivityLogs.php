<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    public ?string $quickFilter = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('filter_ip_assets')
                ->label('IP Assets')
                ->icon('heroicon-o-server-stack')
                ->color('primary')
                ->outlined()
                ->action(function () {
                    $this->quickFilter = 'ip_assets';
                    $this->resetTable();
                }),
            Actions\Action::make('filter_workflows')
                ->label('Workflows')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('primary')
                ->outlined()
                ->action(function () {
                    $this->quickFilter = 'workflows';
                    $this->resetTable();
                }),
            Actions\Action::make('filter_logins')
                ->label('Login/Logout')
                ->icon('heroicon-o-key')
                ->color('info')
                ->outlined()
                ->action(function () {
                    $this->quickFilter = 'logins';
                    $this->resetTable();
                }),
            Actions\Action::make('filter_created')
                ->label('Creations')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->outlined()
                ->action(function () {
                    $this->quickFilter = 'created';
                    $this->resetTable();
                }),
            Actions\Action::make('filter_updated')
                ->label('Updates')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->outlined()
                ->action(function () {
                    $this->quickFilter = 'updated';
                    $this->resetTable();
                }),
            Actions\Action::make('filter_deleted')
                ->label('Deletions')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->outlined()
                ->action(function () {
                    $this->quickFilter = 'deleted';
                    $this->resetTable();
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        // 根据快速过滤器修改查询
        match($this->quickFilter) {
            'ip_assets' => $query->where('model_type', 'App\\Models\\IpAsset'),
            'workflows' => $query->where('model_type', 'App\\Models\\Workflow'),
            'logins' => $query->whereIn('action', ['login', 'logout']),
            'created' => $query->where('action', 'created'),
            'updated' => $query->where('action', 'updated'),
            'deleted' => $query->where('action', 'deleted'),
            default => null,
        };

        return $query;
    }
}
