<?php

namespace App\Filament\Widgets\Dashboard;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class BackupStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.backup-status-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = -1; // Show at top
    
    public function getBackupMetadata(): ?array
    {
        if (!Storage::exists('backups/.last_backup.json')) {
            return null;
        }
        
        return json_decode(Storage::get('backups/.last_backup.json'), true);
    }
    
    public function createBackup()
    {
        try {
            Artisan::call('backup:data');
            
            Notification::make()
                ->success()
                ->title('Backup Created')
                ->body('System backup completed successfully.')
                ->send();
                
            // Refresh the widget
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Backup Failed')
                ->body($e->getMessage())
                ->send();
        }
    }
    
    public function downloadLatestBackup()
    {
        $metadata = $this->getBackupMetadata();
        
        if (!$metadata) {
            Notification::make()
                ->warning()
                ->title('No Backup Found')
                ->body('No backup files available.')
                ->send();
            return;
        }
        
        $path = $metadata['last_backup_path'];
        
        if (!file_exists($path)) {
            Notification::make()
                ->danger()
                ->title('File Not Found')
                ->body('Backup file does not exist.')
                ->send();
            return;
        }
        
        return response()->download($path, $metadata['last_backup_file']);
    }
}
