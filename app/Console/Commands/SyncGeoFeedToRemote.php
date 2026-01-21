<?php

namespace App\Console\Commands;

use App\Services\GeoFeedService;
use Illuminate\Console\Command;

class SyncGeoFeedToRemote extends Command
{
    protected $signature = 'geofeed:sync-remote {--mode=test : Mode: test or production}';

    protected $description = 'Sync GeoFeed data from OA database to remote server';

    public function handle(GeoFeedService $service): int
    {
        $mode = $this->option('mode');
        $filename = $mode === 'production' ? 'geofeed.csv' : 'geofeed.test.csv';
        
        $this->info("Starting GeoFeed sync to remote ({$mode} mode)...");
        $this->info("Target file: {$filename}");
        
        try {
            $result = $service->uploadTestFeed($filename);
            
            if ($result['uploaded']) {
                $this->info('✓ Successfully synced to remote server.');
                $this->info("  Method: {$result['method']}");
                $this->info("  Local: {$result['local_path']}");
                return 0;
            } else {
                $this->error('✗ Failed to sync: ' . ($result['message'] ?? 'Unknown error'));
                return 1;
            }
        } catch (\Throwable $e) {
            $this->error('✗ Exception: ' . $e->getMessage());
            return 1;
        }
    }
}
