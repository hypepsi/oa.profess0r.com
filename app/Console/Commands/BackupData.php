<?php

namespace App\Console\Commands;

use App\Exports\SystemBackupExport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupData extends Command
{
    protected $signature = 'backup:data';
    protected $description = 'Backup all system data to Excel file';

    public function handle()
    {
        $this->info('Starting system backup...');
        
        // Generate filename
        $filename = 'oa_backup_' . now('Asia/Shanghai')->format('Y-m-d_His') . '.xlsx';
        $path = 'backups/' . $filename;
        
        // Export to Excel
        Excel::store(new SystemBackupExport(), $path, 'local');
        
        // Clean old backups (keep last 30 days)
        $this->cleanOldBackups();
        
        // Store backup metadata
        $this->storeBackupMetadata($filename, $path);
        
        $this->info('Backup completed: ' . $filename);
        $this->info('Location: storage/app/' . $path);
        
        return 0;
    }
    
    private function cleanOldBackups()
    {
        $files = Storage::files('backups');
        $cutoffDate = now()->subDays(30);
        
        foreach ($files as $file) {
            if (Storage::lastModified($file) < $cutoffDate->timestamp) {
                Storage::delete($file);
                $this->info('Deleted old backup: ' . basename($file));
            }
        }
    }
    
    private function storeBackupMetadata($filename, $path)
    {
        $fullPath = storage_path('app/private/' . $path);
        $metadata = [
            'last_backup_time' => now('Asia/Shanghai')->toDateTimeString(),
            'last_backup_file' => $filename,
            'last_backup_path' => $fullPath,
            'last_backup_size' => file_exists($fullPath) ? filesize($fullPath) : 0,
        ];
        
        Storage::put('backups/.last_backup.json', json_encode($metadata, JSON_PRETTY_PRINT));
    }
}
