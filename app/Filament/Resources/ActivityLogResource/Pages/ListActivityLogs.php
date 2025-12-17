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
            Actions\Action::make('filter_financial')
                ->label('Financial')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->outlined()
                ->action(function () {
                    $this->quickFilter = 'financial';
                    $this->resetTable();
                }),
            
            Actions\Action::make('filter_assets')
                ->label('Assets')
                ->icon('heroicon-o-server-stack')
                ->color('primary')
                ->outlined()
                ->action(function () {
                    $this->quickFilter = 'assets';
                    $this->resetTable();
                }),
            
            Actions\Action::make('filter_people')
                ->label('People')
                ->icon('heroicon-o-user-group')
                ->color('info')
                ->outlined()
                ->action(function () {
                    $this->quickFilter = 'people';
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
            
            Actions\Action::make('filter_auth')
                ->label('Auth')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->outlined()
                ->action(function () {
                    $this->quickFilter = 'auth';
                    $this->resetTable();
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        // 根据快速过滤器修改查询
        match($this->quickFilter) {
            'financial' => $query->where(function ($q) {
                $q->whereIn('model_type', [
                    'App\\Models\\CustomerBillingPayment',
                    'App\\Models\\BillingPaymentRecord',
                    'App\\Models\\BillingOtherItem',
                    'App\\Models\\IncomeOtherItem',
                    'App\\Models\\ProviderExpensePayment',
                    'App\\Models\\ExpensePaymentRecord',
                ])
                ->orWhereIn('action', [
                    'payment_recorded',
                    'invoice_updated',
                    'payment_waived',
                    'payment_reset',
                    'expense_payment_recorded',
                    'expense_invoice_updated',
                    'expense_waived',
                    'expense_reset',
                ]);
            }),
            
            'assets' => $query->whereIn('model_type', [
                'App\\Models\\IpAsset',
                'App\\Models\\Device',
                'App\\Models\\Location',
            ]),
            
            'people' => $query->whereIn('model_type', [
                'App\\Models\\Customer',
                'App\\Models\\Employee',
                'App\\Models\\Provider',
                'App\\Models\\IptProvider',
                'App\\Models\\DatacenterProvider',
            ]),
            
            'workflows' => $query->whereIn('model_type', [
                'App\\Models\\Workflow',
                'App\\Models\\WorkflowUpdate',
            ]),
            
            'auth' => $query->whereIn('action', ['login', 'logout']),
            
            default => null,
        };

        return $query;
    }
}
