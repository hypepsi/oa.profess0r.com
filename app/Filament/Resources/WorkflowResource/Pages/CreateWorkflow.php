<?php

namespace App\Filament\Resources\WorkflowResource\Pages;

use App\Filament\Resources\WorkflowResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkflow extends CreateRecord
{
    protected static string $resource = WorkflowResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = auth()->id();
        $data['status'] = $data['status'] ?? 'open';
        
        // Ensure due_at is set to start of day (00:00:00) in Beijing time
        if (isset($data['due_at'])) {
            $data['due_at'] = \Illuminate\Support\Carbon::parse($data['due_at'], 'Asia/Shanghai')
                ->startOfDay()
                ->toDateString();
        }
        
        return $data;
    }
}
