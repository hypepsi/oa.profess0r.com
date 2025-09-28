<?php

namespace App\Filament\Resources\WorkflowResource\RelationManagers;

use App\Models\WorkflowUpdate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UpdatesRelationManager extends RelationManager
{
    protected static string $relationship = 'updates';
    protected static ?string $title = 'Updates'; // Tab label
    protected static ?string $recordTitleAttribute = 'message';

    public function form(Form $form): Form
    {
        // Parent record (Workflow) is available via $this->getOwnerRecord()
        $requiresEvidence = (bool) ($this->getOwnerRecord()?->taskType?->requires_evidence);

        return $form
            ->schema([
                Forms\Components\Textarea::make('message')
                    ->label('Update message')
                    ->rows(3)
                    ->required(),

                Forms\Components\FileUpload::make('attachments')
                    ->label('Evidence (required for this task type)')
                    ->multiple()
                    ->reorderable()
                    ->directory('workflow-updates/' . $this->getOwnerRecord()->id)
                    ->disk('public')
                    ->preserveFilenames()
                    ->downloadable()
                    ->openable()
                    ->visible($requiresEvidence), // only when task type requires evidence
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('User')->sortable(),
                Tables\Columns\TextColumn::make('message')->label('Message')->limit(80),
                Tables\Columns\TextColumn::make('attachments')
                    ->label('Files')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' file(s)' : '0')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->dateTime('Y-m-d H:i'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Submit update')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = Auth::id();
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}
