<?php

namespace App\Console\Commands;

use App\Services\GeoFeedService;
use Illuminate\Console\Command;

class SyncGeoFeedTest extends Command
{
    protected $signature = 'geofeed:sync-test';
    protected $description = 'Generate and upload geofeed.test.csv from current IP assets';

    public function handle(GeoFeedService $service): int
    {
        $this->info('Generating GeoFeed test file...');
        $result = $service->uploadTestFeed();

        if ($result['uploaded'] ?? false) {
            $this->info('GeoFeed uploaded successfully.');
            $this->line('Local file: ' . ($result['local_path'] ?? ''));
            return 0;
        }

        $this->error('GeoFeed upload failed.');
        $this->line('Reason: ' . ($result['message'] ?? 'Unknown error'));
        $this->line('Local file: ' . ($result['local_path'] ?? ''));

        return 1;
    }
}
