<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    public ?string $quickFilter = null;

    protected function getHeaderActions(): array
    {
        return [
            // 快速分类按钮
            Actions\Action::make('filter_income')
                ->label('Income')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success')
                ->outlined()
                ->action(function () {
                    $this->quickFilter = 'income';
                    $this->resetTable();
                }),
            
            Actions\Action::make('filter_expense')
                ->label('Expense')
                ->icon('heroicon-o-arrow-trending-down')
                ->color('danger')
                ->outlined()
                ->action(function () {
                    $this->quickFilter = 'expense';
                    $this->resetTable();
                }),
            
            Actions\Action::make('filter_assets')
                ->label('Assets')
                ->icon('heroicon-o-server')
                ->color('primary')
                ->outlined()
                ->action(function () {
                    $this->quickFilter = 'assets';
                    $this->resetTable();
                }),
            
            Actions\Action::make('filter_workflows')
                ->label('Workflows')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning')
                ->outlined()
                ->action(function () {
                    $this->quickFilter = 'workflows';
                    $this->resetTable();
                }),
            
            Actions\Action::make('filter_system')
                ->label('System')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->outlined()
                ->action(function () {
                    $this->quickFilter = 'system';
                    $this->resetTable();
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        // 根据快速过滤器修改查询
        match($this->quickFilter) {
            // 收入相关：客户账单、收款记录、附加费用、其他收入
            'income' => $query->where(function ($q) {
                $q->whereIn('model_type', [
                    'App\\Models\\Customer',
                    'App\\Models\\CustomerBillingPayment',
                    'App\\Models\\BillingPaymentRecord',
                    'App\\Models\\BillingOtherItem',
                    'App\\Models\\IncomeOtherItem',
                ])
                ->orWhereIn('action', [
                    'payment_recorded',
                    'invoice_updated',
                    'payment_waived',
                    'payment_reset',
                ]);
            }),
            
            // 支出相关：供应商费用、付款记录
            'expense' => $query->where(function ($q) {
                $q->whereIn('model_type', [
                    'App\\Models\\Provider',
                    'App\\Models\\IptProvider',
                    'App\\Models\\DatacenterProvider',
                    'App\\Models\\ProviderExpensePayment',
                    'App\\Models\\ExpensePaymentRecord',
                ])
                ->orWhereIn('action', [
                    'expense_payment_recorded',
                    'expense_invoice_updated',
                    'expense_waived',
                    'expense_reset',
                ]);
            }),
            
            // 资产相关：IP资产、设备、位置、员工、文档
            'assets' => $query->whereIn('model_type', [
                'App\\Models\\IpAsset',
                'App\\Models\\Device',
                'App\\Models\\Location',
                'App\\Models\\Employee',
                'App\\Models\\Document',
            ]),
            
            // 工作流相关
            'workflows' => $query->whereIn('model_type', [
                'App\\Models\\Workflow',
                'App\\Models\\WorkflowUpdate',
            ]),
            
            // 系统相关：登录、登出、用户管理
            'system' => $query->where(function ($q) {
                $q->whereIn('action', ['login', 'logout'])
                  ->orWhere('model_type', 'App\\Models\\User');
            }),
            
            default => null,
        };

        return $query;
    }

    protected function getTableActions(): array
    {
        return array_merge(
            parent::getTableActions(),
            [
                // 删除当前筛选结果的操作
                Actions\Action::make('delete_filtered')
                    ->label('Delete Filtered')
                    ->icon('heroicon-o-funnel')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Filtered Logs')
                    ->modalDescription('This will delete only the logs matching your current filters.')
                    ->modalSubmitActionLabel('Delete Filtered')
                    ->action(function () {
                        $query = $this->getFilteredTableQuery();
                        $count = $query->count();
                        
                        if ($count > 0) {
                            $query->delete();
                            Notification::make()
                                ->success()
                                ->title('Logs Deleted')
                                ->body("Successfully deleted {$count} filtered log entries.")
                                ->send();
                            $this->resetTable();
                        } else {
                            Notification::make()
                                ->warning()
                                ->title('No Logs')
                                ->body('No logs matched the current filters.')
                                ->send();
                        }
                    })
                    ->visible(fn () => $this->quickFilter !== null),
            ]
        );
    }
}
