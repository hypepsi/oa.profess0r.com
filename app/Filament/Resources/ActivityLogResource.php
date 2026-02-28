<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Activity Logs';
    protected static ?string $pluralModelLabel = 'Activity Logs';
    protected static ?string $modelLabel       = 'Activity Log';
    protected static ?int    $navigationSort   = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    // ------------------------------------------------------------------
    // Table
    // ------------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                // Timestamp
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('Y-m-d H:i:s', 'Asia/Shanghai')
                    ->fontFamily(FontFamily::Mono)
                    ->sortable()
                    ->width('165px'),

                // Operator
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Operator')
                    ->default('System')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable(),

                // Category (extensible badge)
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => ActivityLog::getCategoryColor($state))
                    ->formatStateUsing(fn (string $state): string =>
                        ActivityLog::getCategoryOptions()[$state] ?? ucwords(str_replace('_', ' ', $state))
                    )
                    ->sortable(),

                // Action
                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state): string => match(true) {
                        in_array($state, [
                            'created', 'payment_recorded', 'expense_payment_recorded',
                            'document_uploaded', 'login',
                        ]) => 'success',

                        in_array($state, [
                            'updated', 'invoice_updated', 'expense_invoice_updated',
                            'workflow_updated', 'document_updated',
                            'workflow_status_changed', 'workflow_assigned',
                            'ip_asset_status_changed', 'ip_asset_customer_changed',
                            'ip_asset_price_changed', 'ip_asset_cost_changed',
                            'payment_waived', 'expense_waived',
                        ]) => 'warning',

                        in_array($state, [
                            'deleted', 'force_deleted', 'document_deleted',
                            'payment_reset', 'expense_reset',
                        ]) => 'danger',

                        $state === 'logout' => 'gray',

                        default => 'primary',
                    })
                    ->formatStateUsing(fn (string $state): string =>
                        ucwords(str_replace('_', ' ', $state))
                    )
                    ->sortable()
                    ->searchable(),

                // Description (primary content)
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->wrap()
                    ->limit(100),

                // Target model
                Tables\Columns\TextColumn::make('model_type')
                    ->label('Target')
                    ->formatStateUsing(fn (?string $state): string =>
                        $state ? class_basename($state) : '—'
                    )
                    ->description(fn (ActivityLog $record): string =>
                        $record->model_id ? 'ID: ' . $record->model_id : ''
                    )
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                // IP Address
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->fontFamily(FontFamily::Mono)
                    ->copyable()
                    ->copyMessage('IP copied')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // User Agent
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // ---------------------------------------------------------------
            // Filters
            // ---------------------------------------------------------------
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options(ActivityLog::getCategoryOptions())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('action')
                    ->label('Action Type')
                    ->options([
                        'created'                   => 'Created',
                        'updated'                   => 'Updated',
                        'deleted'                   => 'Deleted',
                        'login'                     => 'Login',
                        'logout'                    => 'Logout',
                        'payment_recorded'          => 'Payment Recorded',
                        'invoice_updated'           => 'Invoice Updated',
                        'payment_waived'            => 'Payment Waived',
                        'payment_reset'             => 'Payment Reset',
                        'expense_payment_recorded'  => 'Expense Payment Recorded',
                        'expense_invoice_updated'   => 'Expense Invoice Updated',
                        'expense_waived'            => 'Expense Waived',
                        'expense_reset'             => 'Expense Reset',
                        'workflow_created'          => 'Workflow Created',
                        'workflow_updated'          => 'Workflow Updated',
                        'workflow_status_changed'   => 'Workflow Status Changed',
                        'workflow_assigned'         => 'Workflow Assigned',
                        'workflow_comment_added'    => 'Workflow Comment Added',
                        'ip_asset_status_changed'   => 'IP Asset Status Changed',
                        'ip_asset_customer_changed' => 'IP Asset Customer Changed',
                        'ip_asset_price_changed'    => 'IP Asset Price Changed',
                        'ip_asset_cost_changed'     => 'IP Asset Cost Changed',
                        'document_uploaded'         => 'Document Uploaded',
                        'document_updated'          => 'Document Updated',
                        'document_deleted'          => 'Document Deleted',
                    ])
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Operator')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->placeholder('All users'),

                Tables\Filters\Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query
                            ->when($data['from'],  fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'], fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
                    )
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'])  $indicators[] = 'From: ' . $data['from'];
                        if ($data['until']) $indicators[] = 'Until: ' . $data['until'];
                        return $indicators;
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)

            // ---------------------------------------------------------------
            // Row actions
            // ---------------------------------------------------------------
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('View Details')
                    ->modalHeading('Log Details')
                    ->modalWidth('2xl')
                    ->modalContent(fn (ActivityLog $record) =>
                        view('filament.resources.activity-log-resource.view-details', compact('record'))
                    ),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Delete')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Log Entry')
                    ->modalDescription('This log entry will be permanently deleted.')
                    ->successNotificationTitle('Log entry deleted'),
            ])

            // ---------------------------------------------------------------
            // Bulk actions
            // ---------------------------------------------------------------
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Logs')
                        ->modalDescription('The selected log entries will be permanently deleted. This action cannot be undone.')
                        ->successNotificationTitle('Selected logs deleted'),
                ]),
            ])

            // ---------------------------------------------------------------
            // Header (management) actions
            // ---------------------------------------------------------------
            ->headerActions([
                // Delete filtered
                Tables\Actions\Action::make('delete_filtered')
                    ->label('Delete Filtered')
                    ->icon('heroicon-o-funnel')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Filtered Logs')
                    ->modalDescription(
                        'This will permanently delete all log entries that match your current tab, search, and filter selection.'
                    )
                    ->modalSubmitActionLabel('Delete Filtered Logs')
                    ->action(function (Tables\Table $table) {
                        $query = $table->getFilteredTableQuery();
                        $count = $query->count();

                        if ($count > 0) {
                            $query->delete();
                            Notification::make()
                                ->success()
                                ->title('Filtered Logs Deleted')
                                ->body("Deleted {$count} log " . ($count === 1 ? 'entry' : 'entries') . '.')
                                ->send();
                        } else {
                            Notification::make()
                                ->info()
                                ->title('No Matching Logs')
                                ->body('No log entries matched the current filters.')
                                ->send();
                        }
                    }),

                // Clean by age
                Tables\Actions\Action::make('clean_by_age')
                    ->label('Clean by Age')
                    ->icon('heroicon-o-calendar-days')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\Select::make('days')
                            ->label('Delete logs older than')
                            ->options([
                                7   => '7 days',
                                14  => '14 days',
                                30  => '30 days (1 month)',
                                60  => '60 days (2 months)',
                                90  => '90 days (3 months)',
                                180 => '180 days (6 months)',
                                365 => '365 days (1 year)',
                            ])
                            ->default(90)
                            ->required()
                            ->helperText('All log entries created before this period will be permanently deleted.'),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Clean Old Logs')
                    ->modalDescription('Select a retention period. All logs older than the chosen period will be permanently deleted.')
                    ->modalSubmitActionLabel('Delete Old Logs')
                    ->action(function (array $data) {
                        $days  = (int) $data['days'];
                        $count = ActivityLog::where('created_at', '<', now()->subDays($days))->delete();

                        Notification::make()
                            ->success()
                            ->title('Old Logs Deleted')
                            ->body("Deleted {$count} log " . ($count === 1 ? 'entry' : 'entries') . " older than {$days} days.")
                            ->send();
                    }),

                // Nuclear option — delete everything
                Tables\Actions\Action::make('delete_all')
                    ->label('Delete All')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete ALL Activity Logs')
                    ->modalDescription(
                        'This will permanently delete every activity log in the system regardless of any active filters. This action cannot be undone.'
                    )
                    ->modalSubmitActionLabel('Yes, Delete All Logs')
                    ->action(function () {
                        $count = ActivityLog::count();
                        ActivityLog::query()->delete();

                        Notification::make()
                            ->success()
                            ->title('All Logs Deleted')
                            ->body("Deleted all {$count} log " . ($count === 1 ? 'entry' : 'entries') . ' from the system.')
                            ->send();
                    }),
            ])

            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('60s')
            ->emptyStateIcon('heroicon-o-document-magnifying-glass')
            ->emptyStateHeading('No activity logs found')
            ->emptyStateDescription('Activity logs appear here as users interact with the system.');
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
