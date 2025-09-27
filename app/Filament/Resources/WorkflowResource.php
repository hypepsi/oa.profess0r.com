<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkflowResource\Pages;
use App\Models\Workflow;
use App\Models\TaskType;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class WorkflowResource extends Resource
{
    protected static ?string $model = Workflow::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Workflows';
    protected static ?string $pluralLabel = 'Workflows';
    protected static ?string $modelLabel = 'Workflow';

    protected static ?string $navigationGroup = null;
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('task_type_id')
                        ->label('Task Type')
                        ->options(fn () => TaskType::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()
                        )
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Forms\Components\Select::make('priority')
                        ->label('Priority')
                        ->options([
                            'low' => 'Low',
                            'normal' => 'Normal',
                            'high' => 'High',
                            'urgent' => 'Urgent',
                        ])
                        ->default('normal')
                        ->native(false),

                    // 快捷截止期（选择后自动设置 due_at）
                    Forms\Components\Select::make('due_preset')
                        ->label('Quick Due')
                        ->options([
                            '3' => 'In 3 days',
                            '5' => 'In 5 days',
                            '7' => 'In 7 days',
                        ])
                        ->dehydrated(false)   // 不入库
                        ->reactive()
                        ->afterStateUpdated(function (\Filament\Forms\Set $set, $state) {
                            if ($state) {
                                $set('due_at', Carbon::now()->addDays((int) $state));
                            }
                        })
                        ->helperText('快捷设置截止时间，仍可手动修改。'),

                    Forms\Components\DateTimePicker::make('due_at')
                        ->label('Due At')
                        ->seconds(false),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'open' => 'Open',
                            'in_review' => 'In Review',
                            'follow_up' => 'Follow Up',
                            'approved' => 'Approved',
                            'closed' => 'Closed',
                            'overdue' => 'Overdue',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('open')
                        ->native(false),
                ]),

            Forms\Components\Section::make('People')
                ->columns(2)
                ->schema([
                    Forms\Components\Placeholder::make('owner_info')
                        ->label('Owner (Admin)')
                        ->content(fn () => auth()->user()?->name ?? '—'),

                    Forms\Components\Select::make('assignees')
                        ->label('Assignees')
                        ->options(fn () => Employee::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()
                        )
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->helperText('可多选员工作为执行人'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Title')->searchable(),
                Tables\Columns\TextColumn::make('taskType.name')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('priority')->label('Priority')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('due_at')->label('Due')->dateTime('Y-m-d H:i'),
                Tables\Columns\TextColumn::make('assignees_count')->label('Assignees')->counts('assignees')->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflows::route('/'),
            'create' => Pages\CreateWorkflow::route('/create'),
            'edit' => Pages\EditWorkflow::route('/{record}/edit'),
        ];
    }
}
