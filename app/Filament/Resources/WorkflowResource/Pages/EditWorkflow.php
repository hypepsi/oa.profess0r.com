<?php

namespace App\Filament\Resources\WorkflowResource\Pages;

use App\Filament\Resources\WorkflowResource;
use App\Models\WorkflowUpdate;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;

class EditWorkflow extends EditRecord
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('submit_update')
                ->label('Submit update')
                ->icon('heroicon-m-paper-airplane')
                ->form(function () {
                    $requiresEvidence = (bool) ($this->record?->taskType?->requires_evidence);

                    return [
                        Forms\Components\Textarea::make('message')
                            ->label('Update message')
                            ->rows(4)
                            ->required(),

                        Forms\Components\FileUpload::make('attachments')
                            ->label('Evidence (required for this task type)')
                            ->multiple()
                            ->reorderable()
                            ->directory('workflow-updates/' . $this->record->id)
                            ->disk('public')
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->visible($requiresEvidence),
                    ];
                })
                ->action(function (array $data) {
                    // Normalize files to array
                    if (!empty($data['attachments']) && !is_array($data['attachments'])) {
                        $data['attachments'] = [$data['attachments']];
                    }

                    WorkflowUpdate::create([
                        'workflow_id' => $this->record->id,
                        'user_id'     => auth()->id(),
                        'message'     => $data['message'] ?? null,
                        'attachments' => $data['attachments'] ?? [],
                    ]);

                    // Auto move status to "updated" unless already approved/cancelled
                    if (!in_array($this->record->status, ['approved', 'cancelled'], true)) {
                        $this->record->update(['status' => 'updated']);
                    }

                    $this->notify('success', 'Update submitted.');
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
