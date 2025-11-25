<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
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
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('model_type')
                    ->label('Model Type')
                    ->options([
                        'App\\Models\\IpAsset' => 'IP Asset',
                        'App\\Models\\Device' => 'Device',
                        'App\\Models\\Location' => 'Location',
                        'App\\Models\\Customer' => 'Customer',
                        'App\\Models\\Provider' => 'Provider',
                        'App\\Models\\IptProvider' => 'IPT Provider',
                        'App\\Models\\Employee' => 'Employee',
                        'App\\Models\\Workflow' => 'Workflow',
                        'App\\Models\\WorkflowUpdate' => 'Workflow Update',
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
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s') // 每30秒自动刷新
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
