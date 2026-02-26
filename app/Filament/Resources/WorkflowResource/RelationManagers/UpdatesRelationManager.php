<?php

namespace App\Filament\Resources\WorkflowResource\RelationManagers;

use App\Models\WorkflowUpdate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UpdatesRelationManager extends RelationManager
{
    protected static string $relationship = 'updates';
    protected static ?string $title = 'Updates';
    protected static ?string $recordTitleAttribute = 'content';

    public function form(Form $form): Form
    {
        $workflow = $this->getOwnerRecord();
        $requiresEvidence = $workflow->require_evidence ?? false;
        
        return $form
            ->schema([
                Forms\Components\Textarea::make('content')
                    ->label('Update message')
                    ->rows(4)
                    ->required()
                    ->columnSpanFull()
                    ->placeholder('Enter your update message...'),

                Forms\Components\FileUpload::make('attachments')
                    ->label($requiresEvidence ? 'Evidence (Required)' : 'Attachments')
                    ->multiple()
                    ->reorderable()
                    ->directory('workflow-updates/' . $this->getOwnerRecord()->id)
                    ->disk('public')
                    ->preserveFilenames()
                    ->downloadable()
                    ->openable()
                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                    ->maxSize(10240) // 10MB
                    ->required($requiresEvidence)
                    ->helperText($requiresEvidence 
                        ? 'Evidence is required for this workflow. Please upload files.' 
                        : 'Optional: Upload files related to this update')
                    ->hint($requiresEvidence ? 'At least one file is required' : null)
                    ->hintColor('danger'),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('content')
                    ->label('Message')
                    ->limit(100)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('attachments')
                    ->label('Attachments')
                    ->formatStateUsing(function ($state) {
                        if (empty($state) || !is_array($state)) {
                            return '—';
                        }
                        $count = count($state);
                        return $count . ' file' . ($count > 1 ? 's' : '');
                    })
                    ->badge()
                    ->color(function ($state) {
                        if (empty($state) || !is_array($state) || count($state) === 0) {
                            return 'gray';
                        }
                        return 'success';
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i', 'Asia/Shanghai')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add update')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = Auth::id();
                        $data['workflow_id'] = $this->getOwnerRecord()->id;
                        
                        // Validate evidence requirement
                        $workflow = $this->getOwnerRecord();
                        if ($workflow->require_evidence) {
                            if (empty($data['attachments']) || 
                                (is_array($data['attachments']) && count(array_filter($data['attachments'])) === 0)) {
                                throw new \Illuminate\Validation\ValidationException(
                                    validator([], []),
                                    ['attachments' => ['Evidence is required for this workflow. Please upload at least one file.']]
                                );
                            }
                        }
                        
                        // normalize attachments to array
                        if (!empty($data['attachments']) && !is_array($data['attachments'])) {
                            $data['attachments'] = [$data['attachments']];
                        }
                        return $data;
                    })
                    ->after(function () {
                        // auto set parent to Updated (unless already Approved/Cancelled)
                        $parent = $this->getOwnerRecord();
                        if (!in_array($parent->status, ['approved', 'cancelled'], true)) {
                            $parent->update(['status' => 'updated']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Update Details')
                    ->modalContent(fn ($record) => view('filament.resources.workflow-resource.relation-managers.update-details', ['record' => $record]))
                    ->form([]),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->visible(fn () => auth()->user()?->isAdmin()),
            ])
            ->emptyStateHeading('No updates yet')
            ->emptyStateDescription('Add an update to track progress on this workflow.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}
