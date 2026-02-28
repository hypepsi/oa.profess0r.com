<?php

namespace App\Filament\Resources\EmailAccountResource\Pages;

use App\Filament\Resources\EmailAccountResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Crypt;

class CreateEmailAccount extends CreateRecord
{
    protected static string $resource = EmailAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Encrypt password before storing
        if (!empty($data['password_plain'])) {
            $data['password_encrypted'] = Crypt::encryptString($data['password_plain']);
        }
        unset($data['password_plain']);
        return $data;
    }
}
