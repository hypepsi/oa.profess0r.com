<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkflowResource\Pages;
use App\Models\Workflow;
use App\Filament\Resources\WorkflowResource\RelationManagers\UpdatesRelationManager;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FieldAction;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class WorkflowResource extends Resource
{
    protected static ?string $model = Workflow::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    /** Keep it next to IP Asset */
    public static function getNavigationGroup(): ?string
    {
        if (class_exists(\App\Filament\Resources\IpAssetResource::class)) {
            $cls = \App\Filament\Resources\IpAssetResource::class;
            return $cls::getNavigationGroup();
        }
        return null;
    }

    public static function getNavigationSort(): ?int
    {
        if (class_exists(\App\Filament\Resources\IpAssetResource::class)) {
            $cls = \App\Filament\Resources\IpAssetResource::class;
            return (($cls::getNavigationSort() ?? 0) + 1);
        }
        return 1;
    }

    protected static ?string $modelLabel = 'Workflow';
    protected static ?string $pluralModelLabel = 'Workflows';

    // Hide default navigation item, we'll use custom month-based navigation
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        $isAdmin = auth()->check() && auth()->user()->email === 'admin@bunnycommunications.com';
        
        return $form->schema([
            \Filament\Forms\Components\Section::make('Basic Information')
                ->description('Enter the basic details for this workflow')
                ->schema([
                    \Filament\Forms\Components\TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Enter workflow title')
                        ->disabled(fn ($record) => $record && !$isAdmin)
                        ->columnSpanFull(),

                    \Filament\Forms\Components\Select::make('client_id')
                        ->label('Client')
                        ->relationship('client', 'name', fn ($query) => $query->where('active', true))
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->placeholder('No client (optional)')
                        ->helperText('Optional: Select a client for this workflow')
                        ->disabled(fn ($record) => $record && !$isAdmin),

                    \Filament\Forms\Components\Select::make('assignees')
                        ->label('Assignees')
                        ->relationship('assignees', 'name', fn ($query) => $query->where('is_active', true))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->placeholder('Select employees')
                        ->helperText('Select one or more employees to assign this workflow')
                        ->required()
                        ->disabled(fn ($record) => $record && !$isAdmin),

                    \Filament\Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(4)
                        ->placeholder('Enter workflow description')
                        ->columnSpanFull()
                        ->disabled(fn ($record) => $record && !$isAdmin),

                    \Filament\Forms\Components\DatePicker::make('due_at')
                        ->label('Due Date')
                        ->native(false)
                        ->required()
                        ->displayFormat('Y-m-d')
                        ->suffixActions([
                            FieldAction::make('due_3d')
                                ->label('3d')
                                ->button()
                                ->color('gray')
                                ->size('sm')
                                ->extraAttributes(['class'=>'text-xs'])
                                ->action(fn (Set $set) => $set('due_at', Carbon::now('Asia/Shanghai')->addDays(3)->startOfDay()->toDateString())),
                            FieldAction::make('due_7d')
                                ->label('7d')
                                ->button()
                                ->color('gray')
                                ->size('sm')
                                ->extraAttributes(['class'=>'text-xs'])
                                ->action(fn (Set $set) => $set('due_at', Carbon::now('Asia/Shanghai')->addDays(7)->startOfDay()->toDateString())),
                            FieldAction::make('due_14d')
                                ->label('14d')
                                ->button()
                                ->color('gray')
                                ->size('sm')
                                ->extraAttributes(['class'=>'text-xs'])
                                ->action(fn (Set $set) => $set('due_at', Carbon::now('Asia/Shanghai')->addDays(14)->startOfDay()->toDateString())),
                        ])
                        ->default(fn () => Carbon::now('Asia/Shanghai')->addDays(3)->startOfDay())
                        ->helperText('Set the due date for this workflow (will be set to 00:00:00 on the selected day)')
                        ->disabled(fn ($record) => $record && !$isAdmin),
                    
                    \Filament\Forms\Components\Toggle::make('require_evidence')
                        ->label('Require Evidence')
                        ->helperText('If enabled, employees must upload evidence (screenshots/files) when updating this workflow')
                        ->default(false)
                        ->disabled(fn ($record) => $record && !$isAdmin)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            \Filament\Forms\Components\Section::make('Status & Priority')
                ->columns(2)
                ->schema([
                    \Filament\Forms\Components\Select::make('priority')
                        ->label('Priority')
                        ->options([
                            'low' => 'Low',
                            'normal' => 'Normal',
                            'high' => 'High',
                            'urgent' => 'Urgent',
                        ])
                        ->default('normal')
                        ->native(false)
                        ->required()
                        ->disabled(fn ($record) => $record && !$isAdmin)
                        ->helperText('Set the priority level for this workflow'),

                    \Filament\Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'open' => 'Open',
                            'updated' => 'Updated',
                            'approved' => 'Approved',
                            'overdue' => 'Overdue',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('open')
                        ->native(false)
                        ->required()
                        ->disabled(fn ($record) => $record && !$isAdmin)
                        ->helperText('Current status of the workflow')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            // Auto mark as overdue when status is overdue
                            if ($state === 'overdue') {
                                $set('is_overdue', true);
                            }
                        }),
                    
                    \Filament\Forms\Components\Toggle::make('is_overdue')
                        ->label('Mark as Overdue')
                        ->helperText('Will affect salary calculation')
                        ->default(false)
                        ->disabled(fn ($record) => $record && !$isAdmin)
                        ->columnSpanFull(),
                    
                    \Filament\Forms\Components\TextInput::make('deduction_amount')
                        ->label('Deduction Amount (USD)')
                        ->numeric()
                        ->default(0)
                        ->prefix('$')
                        ->helperText('Amount to deduct from salary if overdue')
                        ->disabled(fn ($record) => $record && !$isAdmin)
                        ->visible(fn (callable $get) => $get('is_overdue') === true),
                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query->with(['updates.user', 'client', 'assignees'])
                    ->withCount('updates');
            })
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable(query: function ($query, $search) {
                        return $query->where('title', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhereHas('client', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('assignees', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%");
                            });
                    })
                    ->sortable()
                    ->weight('medium')
                    ->limit(40),
                \Filament\Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->placeholder('—')
                    ->default('No client')
                    ->badge()
                    ->color('gray')
                    ->limit(20)
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('assignees_list')
                    ->label('Assignees')
                    ->getStateUsing(fn (Workflow $record) => $record->assignees->pluck('name')->join(', ') ?: '—')
                    ->badge()
                    ->color('info')
                    ->limit(20),
                \Filament\Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'low' => 'gray',
                        'normal' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state, Workflow $record) => match ($state) {
                        'open' => ($record->due_at && $record->due_at->isPast()) ? 'Overdue' : 'Open',
                        'updated' => 'Updated',
                        'approved' => 'Approved',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (string $state, Workflow $record) => match ($state) {
                        'open' => ($record->due_at && $record->due_at->isPast()) ? 'danger' : 'gray',
                        'overdue' => 'danger',
                        'approved' => 'success',
                        'updated' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('due_at')
                    ->label('Due Date')
                    ->date('Y-m-d', 'Asia/Shanghai')
                    ->sortable()
                    ->color(fn (Workflow $record) => $record->due_at && $record->due_at->isPast() && $record->status !== 'approved' ? 'danger' : null),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date('Y-m-d', 'Asia/Shanghai')
                    ->sortable()
                    ->default('—')
                    ->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved')
                    ->date('Y-m-d', 'Asia/Shanghai')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                \Filament\Tables\Columns\TextColumn::make('last_update_at')
                    ->label('Last Update')
                    ->getStateUsing(function (Workflow $record) {
                        $lastUpdate = $record->updates()->with('user')->latest('created_at')->first();
                        if (!$lastUpdate) {
                            return null;
                        }
                        $now = Carbon::now('Asia/Shanghai');
                        $lastUpdateTime = $lastUpdate->created_at->setTimezone('Asia/Shanghai');
                        $userName = $lastUpdate->user->name ?? 'Unknown';
                        
                        // Calculate hours and minutes
                        $totalMinutes = (int) $lastUpdateTime->diffInMinutes($now);
                        $hours = (int) floor($totalMinutes / 60);
                        $minutes = $totalMinutes % 60;
                        
                        // Format time display in English
                        if ($totalMinutes < 1) {
                            $timeAgo = 'just now';
                        } elseif ($hours < 1) {
                            $timeAgo = $minutes . ' min' . ($minutes > 1 ? 's' : '') . ' ago';
                        } elseif ($minutes == 0) {
                            $timeAgo = $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
                        } else {
                            $timeAgo = $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ' . $minutes . ' min' . ($minutes > 1 ? 's' : '') . ' ago';
                        }
                        
                        return $userName . ' • ' . $timeAgo;
                    })
                    ->placeholder('—')
                    ->sortable()
                    ->tooltip(function (Workflow $record) {
                        $lastUpdate = $record->updates()->with('user')->latest('created_at')->first();
                        if (!$lastUpdate) {
                            return null;
                        }
                        $userName = $lastUpdate->user->name ?? 'Unknown';
                        $dateTime = $lastUpdate->created_at->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s');
                        return $userName . ' • ' . $dateTime;
                    }),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Workflow')
                    ->modalDescription('Are you sure you want to approve this workflow? This action will mark it as completed.')
                    ->visible(fn (Workflow $record) => 
                        in_array($record->status, ['open', 'updated']) && 
                        auth()->user()->email === 'admin@bunnycommunications.com'
                    )
                    ->action(function (Workflow $record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_at' => Carbon::now('Asia/Shanghai'),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Workflow approved')
                            ->body('The workflow has been approved and marked as completed.')
                            ->send();
                    }),
                \Filament\Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil'),
                \Filament\Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->visible(fn () => auth()->user()->email === 'admin@bunnycommunications.com'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'updated' => 'Updated',
                        'approved' => 'Approved',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),
                \Filament\Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ])
                    ->multiple(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UpdatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWorkflows::route('/'),
            'month'  => Pages\ListWorkflowsByMonth::route('/month/{year}/{month}'),
            'create' => Pages\CreateWorkflow::route('/create'),
            'edit'   => Pages\EditWorkflow::route('/{record}/edit'),
        ];
    }
}
