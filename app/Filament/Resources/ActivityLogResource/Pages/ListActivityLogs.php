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
            Actions\ActionGroup::make([
                Actions\Action::make('clean_24h')
                    ->label('24 Hours')
                    ->icon('heroicon-o-clock')
                    ->requiresConfirmation()
                    ->modalHeading('Clean Activity Logs')
                    ->modalDescription('Are you sure you want to delete all activity logs from the last 24 hours? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete')
                    ->modalCancelActionLabel('Cancel')
                    ->action(function () {
                        $this->cleanRecentLogs(24);
                    }),
                Actions\Action::make('clean_7d')
                    ->label('7 Days')
                    ->icon('heroicon-o-calendar')
                    ->requiresConfirmation()
                    ->modalHeading('Clean Activity Logs')
                    ->modalDescription('Are you sure you want to delete all activity logs from the last 7 days? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete')
                    ->modalCancelActionLabel('Cancel')
                    ->action(function () {
                        $this->cleanRecentLogs(7 * 24);
                    }),
                Actions\Action::make('clean_30d')
                    ->label('30 Days')
                    ->icon('heroicon-o-calendar-days')
                    ->requiresConfirmation()
                    ->modalHeading('Clean Activity Logs')
                    ->modalDescription('Are you sure you want to delete all activity logs from the last 30 days? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete')
                    ->modalCancelActionLabel('Cancel')
                    ->action(function () {
                        $this->cleanRecentLogs(30 * 24);
                    }),
                Actions\Action::make('clean_all')
                    ->label('All Logs')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete All Activity Logs')
                    ->modalDescription('Are you sure you want to delete ALL activity logs? This action cannot be undone and will delete every single log record.')
                    ->modalSubmitActionLabel('Yes, delete all')
                    ->modalCancelActionLabel('Cancel')
                    ->action(function () {
                        $this->cleanAllLogs();
                    }),
            ])
                ->label('Clean Logs')
                ->icon('heroicon-o-trash')
                ->color('danger'),
        ];
    }

    public function cleanRecentLogs(int $hours): void
    {
        $cutoffDate = \Carbon\Carbon::now('Asia/Shanghai')->subHours($hours);
        
        // 先统计要删除的数量（最近X时间的日志）
        $countToDelete = \App\Models\ActivityLog::where('created_at', '>=', $cutoffDate)->count();
        
        if ($countToDelete === 0) {
            $timeLabel = match(true) {
                $hours < 24 => "{$hours} hours",
                $hours < 168 => (int)($hours / 24) . " days",
                default => (int)($hours / 24) . " days",
            };
            
            \Filament\Notifications\Notification::make()
                ->title('No logs to clean')
                ->body("There are no activity logs from the last {$timeLabel}.")
                ->warning()
                ->send();
            return;
        }
        
        // 删除最近X时间的日志
        $deletedCount = \App\Models\ActivityLog::where('created_at', '>=', $cutoffDate)->delete();

        $timeLabel = match(true) {
            $hours < 24 => "{$hours} hours",
            $hours < 168 => (int)($hours / 24) . " days",
            default => (int)($hours / 24) . " days",
        };

        \Filament\Notifications\Notification::make()
            ->title('Logs cleaned')
            ->body("Successfully deleted {$deletedCount} activity log(s) from the last {$timeLabel}.")
            ->success()
            ->send();

        $this->resetTable();
    }

    public function cleanAllLogs(): void
    {
        $totalCount = \App\Models\ActivityLog::count();
        
        if ($totalCount === 0) {
            \Filament\Notifications\Notification::make()
                ->title('No logs to clean')
                ->body('There are no activity logs to delete.')
                ->warning()
                ->send();
            return;
        }
        
        $deletedCount = \App\Models\ActivityLog::query()->delete();

        \Filament\Notifications\Notification::make()
            ->title('All logs deleted')
            ->body("Successfully deleted all {$deletedCount} activity log(s).")
            ->success()
            ->send();

        $this->resetTable();
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
            default => null, // 无操作
        };

        return $query;
    }
}
