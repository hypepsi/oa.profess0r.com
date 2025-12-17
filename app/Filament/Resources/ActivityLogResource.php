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
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'login' => 'info',
                        'logout' => 'gray',
                        'payment_recorded', 'expense_payment_recorded' => 'success',
                        'invoice_updated', 'expense_invoice_updated' => 'warning',
                        'payment_waived', 'expense_waived' => 'warning',
                        'payment_reset', 'expense_reset' => 'danger',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (string $state) => ucwords(str_replace('_', ' ', $state)))
                    ->sortable()
                    ->searchable(),

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
                    ->label('Action')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'login' => 'Login',
                        'logout' => 'Logout',
                        'payment_recorded' => 'Income: Payment Recorded',
                        'invoice_updated' => 'Income: Invoice Updated',
                        'payment_waived' => 'Income: Payment Waived',
                        'payment_reset' => 'Income: Payment Reset',
                        'expense_payment_recorded' => 'Expense: Payment Recorded',
                        'expense_invoice_updated' => 'Expense: Invoice Updated',
                        'expense_waived' => 'Expense: Waived',
                        'expense_reset' => 'Expense: Reset',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('model_type')
                    ->label('Module')
                    ->options([
                        'App\\Models\\IpAsset' => 'IP Assets',
                        'App\\Models\\Device' => 'Devices',
                        'App\\Models\\Location' => 'Locations',
                        'App\\Models\\Customer' => 'Customers',
                        'App\\Models\\Provider' => 'IP Providers',
                        'App\\Models\\IptProvider' => 'IPT Providers',
                        'App\\Models\\DatacenterProvider' => 'Datacenter Providers',
                        'App\\Models\\Employee' => 'Employees',
                        'App\\Models\\Workflow' => 'Workflows',
                        'App\\Models\\WorkflowUpdate' => 'Workflow Updates',
                        'App\\Models\\BillingOtherItem' => 'Income Add-ons',
                        'App\\Models\\IncomeOtherItem' => 'Other Income',
                        'App\\Models\\CustomerBillingPayment' => 'Customer Billing',
                        'App\\Models\\ProviderExpensePayment' => 'Provider Expense',
                    ])
                    ->multiple(),

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
                Tables\Actions\Action::make('delete_filtered')
                    ->label('Delete Filtered Logs')
                    ->icon('heroicon-o-funnel')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Filtered Logs')
                    ->modalDescription('This will delete only the logs matching your current filters. All other logs will remain.')
                    ->modalSubmitActionLabel('Delete Filtered Logs')
                    ->action(function (Tables\Table $table) {
                        $query = $table->getFilteredTableQuery();
                        $count = $query->count();
                        $query->delete();
                        
                        Notification::make()
                            ->success()
                            ->title('Filtered Logs Deleted')
                            ->body("Successfully deleted {$count} filtered log entries.")
                            ->send();
                    })
                    ->visible(fn () => true), // Always visible since filters may be applied
                Tables\Actions\Action::make('delete_old_logs')
                    ->label('Clean Old Logs')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\Select::make('days')
                            ->label('Delete logs older than')
                            ->options([
                                7 => '7 days',
                                14 => '14 days',
                                30 => '30 days',
                                60 => '60 days',
                                90 => '90 days',
                                180 => '180 days',
                                365 => '1 year',
                            ])
                            ->default(30)
                            ->required(),
                    ])
                    ->modalHeading('Clean Old Activity Logs')
                    ->modalDescription('This will permanently delete all activity logs older than the selected period.')
                    ->modalSubmitActionLabel('Delete Old Logs')
                    ->action(function (array $data) {
                        $days = $data['days'];
                        $date = now()->subDays($days);
                        $count = ActivityLog::where('created_at', '<', $date)->count();
                        ActivityLog::where('created_at', '<', $date)->delete();
                        
                        Notification::make()
                            ->success()
                            ->title('Old Logs Deleted')
                            ->body("Successfully deleted {$count} log entries older than {$days} days.")
                            ->send();
                    }),
                Tables\Actions\Action::make('delete_all_logs')
                    ->label('Delete ALL Logs')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('⚠️ Delete ALL Activity Logs')
                    ->modalDescription('DANGER: This will permanently delete ALL activity logs from the entire system, regardless of any filters. This action cannot be undone!')
                    ->modalSubmitActionLabel('Yes, Delete Everything')
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
