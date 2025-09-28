<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkflowResource\Pages;
use App\Models\TaskType;
use App\Models\Workflow;
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

    public static function form(Form $form): Form
    {
        return $form->schema([
            \Filament\Forms\Components\Section::make('Basic')
                ->columns(2)
                ->schema([
                    \Filament\Forms\Components\TextInput::make('title')
                        ->label('Title')->required()->maxLength(255),

                    \Filament\Forms\Components\Select::make('task_type_id')
                        ->label('Task Type')
                        ->options(fn () => \App\Models\TaskType::query()
                            ->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->preload()->native(false)->required(),

                    \Filament\Forms\Components\Select::make('client_id')
                        ->label('Clients')
                        ->relationship('client', 'name')->searchable()->preload()->native(false)
                        ->placeholder('Select a client'),

                    \Filament\Forms\Components\Select::make('assignees')
                        ->label('Assignees')
                        ->relationship('assignees', 'name')->multiple()->searchable()->preload()->native(false)
                        ->helperText('Select one or more employees'),

                    \Filament\Forms\Components\Textarea::make('description')
                        ->label('Task description')->rows(3)->columnSpanFull(),

                    \Filament\Forms\Components\DatePicker::make('due_at')
                        ->label('Due (Date)')->native(false)->required()
                        ->suffixActions([
                            FieldAction::make('due_3d')->label('3d')->button()->color('gray')
                                ->extraAttributes(['class'=>'text-xs'])
                                ->action(fn (Set $set) => $set('due_at', \Illuminate\Support\Carbon::now()->addDays(3)->toDateString())),
                            FieldAction::make('due_7d')->label('7d')->button()->color('gray')
                                ->extraAttributes(['class'=>'text-xs'])
                                ->action(fn (Set $set) => $set('due_at', \Illuminate\Support\Carbon::now()->addDays(7)->toDateString())),
                            FieldAction::make('due_14d')->label('14d')->button()->color('gray')
                                ->extraAttributes(['class'=>'text-xs'])
                                ->action(fn (Set $set) => $set('due_at', \Illuminate\Support\Carbon::now()->addDays(14)->toDateString())),
                        ])
                        ->default(fn () => \Illuminate\Support\Carbon::now()->addDays(3)),
                ]),

            \Filament\Forms\Components\Section::make('Status & priority')
                ->columns(3)
                ->schema([
                    \Filament\Forms\Components\Select::make('priority')
                        ->label('Priority')
                        ->options(['low'=>'Low','medium'=>'Medium','high'=>'High'])
                        ->default('medium')->native(false),

                    \Filament\Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'open'=>'Open','updated'=>'Updated','follow_up'=>'Follow Up',
                            'approved'=>'Approved','overdue'=>'Overdue','cancelled'=>'Cancelled',
                        ])->default('open')->native(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('title')->label('Title')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('taskType.name')->label('Type')->badge(),
                \Filament\Tables\Columns\TextColumn::make('client.name')->label('Client')->limit(30),
                \Filament\Tables\Columns\TextColumn::make('assignees_list')
                    ->label('Assignees')
                    ->getStateUsing(fn (Workflow $record) => $record->assignees->pluck('name')->join(', '))
                    ->limit(30),
                \Filament\Tables\Columns\TextColumn::make('priority')->label('Priority')->badge(),
                \Filament\Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                \Filament\Tables\Columns\TextColumn::make('due_at')->label('Due')->date('Y-m-d')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('created_at')->label('Created')->date('Y-m-d')->sortable(),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWorkflows::route('/'),
            'create' => Pages\CreateWorkflow::route('/create'),
            'edit'   => Pages\EditWorkflow::route('/{record}/edit'),
        ];
    }
}
