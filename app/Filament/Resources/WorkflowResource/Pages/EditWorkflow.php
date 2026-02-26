<?php

namespace App\Filament\Resources\WorkflowResource\Pages;

use App\Filament\Resources\WorkflowResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditWorkflow extends EditRecord
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        $isAdmin = $this->isAdmin();
        
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Workflow')
                ->modalDescription('Are you sure you want to approve this workflow? This will mark it as completed.')
                ->modalSubmitActionLabel('Yes, approve')
                ->visible(fn () => $isAdmin && in_array($this->record->status, ['open', 'updated']))
                ->action(function () {
                    $this->record->update([
                        'status' => 'approved',
                        'approved_at' => \Illuminate\Support\Carbon::now('Asia/Shanghai'),
                    ]);
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Workflow approved')
                        ->body('The workflow has been approved and marked as completed.')
                        ->send();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Workflow')
                ->modalDescription('Are you sure you want to delete this workflow? This action cannot be undone.')
                ->visible(fn () => $isAdmin),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Disable all fields if not admin AND not the creator
        if (!$this->isAdmin() && auth()->id() !== $this->record->created_by_user_id) {
            $this->form->disabled();
        }

        return $data;
    }

    protected function isAdmin(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
