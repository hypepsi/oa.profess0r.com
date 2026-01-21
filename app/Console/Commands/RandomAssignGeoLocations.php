<?php

namespace App\Console\Commands;

use App\Models\IpAsset;
use App\Models\GeoFeedLocation;
use Illuminate\Console\Command;

class RandomAssignGeoLocations extends Command
{
    protected $signature = 'geofeed:random-assign {--all : Assign to all assets, not just empty ones}';

    protected $description = 'Randomly assign geo_location from GeoFeedLocation to IP assets';

    public function handle(): int
    {
        $assignAll = $this->option('all');

        $locations = GeoFeedLocation::all();
        if ($locations->isEmpty()) {
            $this->error('No GeoFeedLocation records found. Please import locations first.');
            return 1;
        }

        $this->info('Found ' . $locations->count() . ' GeoFeed locations.');

        $query = IpAsset::query();
        if (!$assignAll) {
            $query->where(function ($q) {
                $q->whereNull('geo_location')
                  ->orWhere('geo_location', '');
            });
        }

        $assets = $query->get();
        if ($assets->isEmpty()) {
            $this->warn('No IP assets found to assign.');
            return 0;
        }

        $this->info('Assigning to ' . $assets->count() . ' assets...');

        $bar = $this->output->createProgressBar($assets->count());
        $bar->start();

        $updated = 0;
        foreach ($assets as $asset) {
            $randomLocation = $locations->random();
            $asset->geo_location = $randomLocation->label;
            $asset->save();
            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Successfully assigned geo_location to {$updated} assets.");

        return 0;
    }
}
