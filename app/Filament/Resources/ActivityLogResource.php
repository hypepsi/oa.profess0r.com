<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Activity Logs';
    protected static ?string $pluralModelLabel = 'Activity Logs';
    protected static ?string $modelLabel = 'Activity Log';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user')->latest())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('Y-m-d H:i:s', 'Asia/Shanghai')
                    ->sortable()
                    ->searchable()
                    ->width('150px'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->default('System')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state) => match($state) {
                        // CRUD Operations
                        'created', 'workflow_created' => 'success',
                        'updated', 'invoice_updated', 'expense_invoice_updated', 'workflow_updated', 'document_updated' => 'warning',
                        'deleted', 'document_deleted' => 'danger',
                        
                        // Authentication
                        'login' => 'info',
                        'logout' => 'gray',
                        
                        // Payments & Financial
                        'payment_recorded', 'expense_payment_recorded' => 'success',
                        'payment_waived', 'expense_waived' => 'warning',
                        'payment_reset', 'expense_reset' => 'danger',
                        
                        // Workflow Operations
                        'workflow_status_changed', 'workflow_assigned' => 'info',
                        'workflow_comment_added' => 'primary',
                        
                        // IP Asset Changes
                        'ip_asset_status_changed', 'ip_asset_customer_changed' => 'info',
                        'ip_asset_price_changed', 'ip_asset_cost_changed' => 'warning',
                        
                        // Document Operations
                        'document_uploaded' => 'success',
                        
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (string $state) => ucwords(str_replace('_', ' ', $state)))
                    ->sortable()
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->wrap()
                    ->limit(80),

                Tables\Columns\TextColumn::make('model_type')
                    ->label('Model')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Action Type')
                    ->options([
                        // Basic CRUD Operations
                        'created' => '✨ Created',
                        'updated' => '✏️ Updated',
                        'deleted' => '🗑️ Deleted',
                        
                        // Authentication
                        'login' => '🔐 User Login',
                        'logout' => '🚪 User Logout',
                        
                        // Income - Customer Billing
                        'payment_recorded' => '💰 Income: Payment Recorded',
                        'invoice_updated' => '📝 Income: Invoice Updated',
                        'payment_waived' => '🎁 Income: Payment Waived',
                        'payment_reset' => '🔄 Income: Payment Reset',
                        
                        // Expense - Provider Payments
                        'expense_payment_recorded' => '💸 Expense: Payment Recorded',
                        'expense_invoice_updated' => '📋 Expense: Invoice Updated',
                        'expense_waived' => '🎁 Expense: Waived',
                        'expense_reset' => '🔄 Expense: Reset',
                        
                        // Workflow Operations
                        'workflow_created' => '📋 Workflow: Created',
                        'workflow_updated' => '📝 Workflow: Updated',
                        'workflow_status_changed' => '🔄 Workflow: Status Changed',
                        'workflow_assigned' => '👤 Workflow: Assigned',
                        'workflow_comment_added' => '💬 Workflow: Comment Added',
                        
                        // IP Asset Specific
                        'ip_asset_status_changed' => '🔄 IP Asset: Status Changed',
                        'ip_asset_customer_changed' => '👥 IP Asset: Customer Changed',
                        'ip_asset_price_changed' => '💵 IP Asset: Price Changed',
                        'ip_asset_cost_changed' => '💰 IP Asset: Cost Changed',
                        
                        // Document Operations
                        'document_uploaded' => '📄 Document: Uploaded',
                        'document_updated' => '✏️ Document: Updated',
                        'document_deleted' => '🗑️ Document: Deleted',
                    ])
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('model_type')
                    ->label('Module/Entity')
                    ->options([
                        // Assets & Infrastructure
                        'App\\Models\\IpAsset' => '🌐 IP Assets',
                        'App\\Models\\Device' => '💻 Devices',
                        'App\\Models\\Location' => '📍 Locations',
                        
                        // People & Organizations
                        'App\\Models\\Customer' => '👥 Customers',
                        'App\\Models\\Employee' => '👔 Employees',
                        
                        // Providers
                        'App\\Models\\Provider' => '🏢 IP Providers',
                        'App\\Models\\IptProvider' => '🔌 IPT Providers',
                        'App\\Models\\DatacenterProvider' => '🏭 Datacenter Providers',
                        
                        // Workflows
                        'App\\Models\\Workflow' => '📋 Workflows',
                        'App\\Models\\WorkflowUpdate' => '💬 Workflow Updates',
                        
                        // Documents
                        'App\\Models\\Document' => '📄 Documents',
                        
                        // Income Management
                        'App\\Models\\BillingOtherItem' => '💰 Income: Add-ons',
                        'App\\Models\\IncomeOtherItem' => '💵 Income: Other Income',
                        'App\\Models\\CustomerBillingPayment' => '📊 Income: Customer Billing',
                        'App\\Models\\BillingPaymentRecord' => '💳 Income: Payment Records',
                        
                        // Expense Management
                        'App\\Models\\ProviderExpensePayment' => '💸 Expense: Provider Payments',
                        'App\\Models\\ExpensePaymentRecord' => '💳 Expense: Payment Records',
                        
                        // System
                        'App\\Models\\User' => '👤 Users',
                    ])
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->placeholder('All Users (including System)'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Activity Log Details')
                    ->modalContent(fn (ActivityLog $record) => view('filament.resources.activity-log-resource.view-details', ['record' => $record])),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Log Entry')
                    ->modalDescription('Are you sure you want to delete this log entry? This action cannot be undone.')
                    ->successNotificationTitle('Log entry deleted'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Logs')
                        ->modalDescription('Are you sure you want to delete the selected log entries? This action cannot be undone.')
                        ->successNotificationTitle('Selected logs deleted'),
                ]),
            ])
            ->headerActions([
                // 删除筛选后的日志
                Tables\Actions\Action::make('delete_filtered')
                    ->label('Delete Filtered')
                    ->icon('heroicon-o-funnel')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Filtered Logs')
                    ->modalDescription('This will delete only the logs that match your current search and filter criteria.')
                    ->modalSubmitActionLabel('Delete Filtered Logs')
                    ->action(function (Tables\Table $table) {
                        $query = $table->getFilteredTableQuery();
                        $count = $query->count();
                        
                        if ($count > 0) {
                            $query->delete();
                            Notification::make()
                                ->success()
                                ->title('Filtered Logs Deleted')
                                ->body("Successfully deleted {$count} log entries.")
                                ->send();
                        } else {
                            Notification::make()
                                ->info()
                                ->title('No Logs to Delete')
                                ->body('No logs matched your current filters.')
                                ->send();
                        }
                    }),
                
                // 按时间清理旧日志
                Tables\Actions\Action::make('delete_by_date')
                    ->label('Clean by Date')
                    ->icon('heroicon-o-calendar-days')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\Select::make('period')
                            ->label('Delete logs older than')
                            ->options([
                                7 => 'Last 7 days',
                                14 => 'Last 14 days',
                                30 => 'Last 30 days (1 month)',
                                60 => 'Last 60 days (2 months)',
                                90 => 'Last 90 days (3 months)',
                                180 => 'Last 180 days (6 months)',
                                365 => 'Last 365 days (1 year)',
                            ])
                            ->default(90)
                            ->required()
                            ->helperText('Logs created before the selected period will be deleted.'),
                    ])
                    ->modalHeading('Clean Old Logs')
                    ->modalDescription('Select a time period. All logs older than this period will be permanently deleted.')
                    ->modalSubmitActionLabel('Delete Old Logs')
                    ->action(function (array $data) {
                        $days = $data['period'];
                        $date = now()->subDays($days);
                        $count = ActivityLog::where('created_at', '<', $date)->count();
                        
                        if ($count > 0) {
                            ActivityLog::where('created_at', '<', $date)->delete();
                            Notification::make()
                                ->success()
                                ->title('Old Logs Deleted')
                                ->body("Successfully deleted {$count} log entries older than {$days} days.")
                                ->send();
                        } else {
                            Notification::make()
                                ->info()
                                ->title('No Old Logs')
                                ->body('No logs older than the selected period were found.')
                                ->send();
                        }
                    }),
                
                // 删除所有日志（危险操作）
                Tables\Actions\Action::make('delete_all')
                    ->label('Delete ALL')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('⚠️ Delete ALL Activity Logs?')
                    ->modalDescription('DANGER: This will permanently delete ALL activity logs in the system, ignoring any filters. This action CANNOT be undone!')
                    ->modalSubmitActionLabel('Yes, Delete ALL Logs')
                    ->action(function () {
                        $count = ActivityLog::count();
                        ActivityLog::query()->delete();
                        
                        Notification::make()
                            ->success()
                            ->title('All Logs Deleted')
                            ->body("Successfully deleted all {$count} log entries from the system.")
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->emptyStateHeading('No activity logs yet')
            ->emptyStateDescription('Activity logs will appear here as users interact with the system.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
