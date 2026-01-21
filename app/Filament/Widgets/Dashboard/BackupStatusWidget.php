<?php

namespace App\Filament\Widgets\Dashboard;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;
use App\Services\GeoFeedService;

class BackupStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.backup-status-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 9999; // Show at bottom
    
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
    
    // GeoFeed related methods
    
    public function getGeoFeedMetadata(): ?array
    {
        if (!Storage::exists('geofeed/.last_sync.json')) {
            return null;
        }
        
        return json_decode(Storage::get('geofeed/.last_sync.json'), true);
    }
    
    public function downloadGeoFeedFromDatabase()
    {
        $service = app(GeoFeedService::class);
        $csv = $service->buildCsv();
        
        $filename = 'geofeed_from_database_' . now('Asia/Shanghai')->format('Y-m-d_His') . '.csv';
        
        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
    
    public function downloadGeoFeedFromRemote()
    {
        try {
            $remoteUrl = 'https://bunnycommunications.com/geofeed.test.csv';
            
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get($remoteUrl);
            
            if (!$response->ok()) {
                Notification::make()
                    ->danger()
                    ->title('Download Failed')
                    ->body('Failed to fetch remote GeoFeed file.')
                    ->send();
                return;
            }
            
            $csv = $response->body();
            $filename = 'geofeed_from_remote_' . now('Asia/Shanghai')->format('Y-m-d_His') . '.csv';
            
            // Also save to local backup directory
            $backupPath = 'backups/geofeed/' . $filename;
            Storage::put($backupPath, $csv);
            
            // Update metadata
            $this->storeGeoFeedBackupMetadata($filename, $backupPath, strlen($csv));
            
            Notification::make()
                ->success()
                ->title('Downloaded')
                ->body('Remote GeoFeed saved to backups.')
                ->send();
            
            return response()->streamDownload(function () use ($csv) {
                echo $csv;
            }, $filename, [
                'Content-Type' => 'text/csv',
            ]);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Download Failed')
                ->body($e->getMessage())
                ->send();
        }
    }
    
    private function storeGeoFeedBackupMetadata(string $filename, string $path, int $size)
    {
        $metadata = [
            'last_backup_time' => now('Asia/Shanghai')->toDateTimeString(),
            'last_backup_file' => $filename,
            'last_backup_path' => storage_path('app/' . $path),
            'last_backup_size' => $size,
            'source' => 'remote',
        ];
        
        Storage::put('backups/geofeed/.last_backup.json', json_encode($metadata, JSON_PRETTY_PRINT));
    }
    
    public function getGeoFeedBackupMetadata(): ?array
    {
        if (!Storage::exists('backups/geofeed/.last_backup.json')) {
            return null;
        }
        
        return json_decode(Storage::get('backups/geofeed/.last_backup.json'), true);
    }
}
