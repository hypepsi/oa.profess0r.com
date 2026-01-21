<?php

namespace App\Console\Commands;

use App\Models\GeoFeedLocation;
use App\Services\GeoFeedService;
use Illuminate\Console\Command;

class ImportGeoFeedLocations extends Command
{
    protected $signature = 'geofeed:import-locations {--source=remote : Source: remote or local} {--url= : Override remote URL}';

    protected $description = 'Import unique GeoFeed locations from remote CSV or local IP assets';

    public function handle(GeoFeedService $service): int
    {
        $source = $this->option('source');

        if ($source === 'remote') {
            return $this->importFromRemote($service);
        }

        if ($source === 'local') {
            return $this->importFromLocalAssets($service);
        }

        $this->error('Invalid source. Use --source=remote or --source=local');
        return 1;
    }

    private function importFromRemote(GeoFeedService $service): int
    {
        $customUrl = $this->option('url');
        
        if ($customUrl) {
            $this->info("Fetching from custom URL: {$customUrl}");
            $remote = $this->fetchFromCustomUrl($customUrl);
        } else {
            $this->info('Fetching remote GeoFeed data from config...');
            $remote = $service->getRemoteIndex();
        }
        
        if (($remote['status'] ?? 'error') !== 'ok') {
            $this->error('Failed to fetch remote GeoFeed: ' . ($remote['error'] ?? 'Unknown error'));
            return 1;
        }

        $records = $remote['records'] ?? [];
        if (empty($records)) {
            $this->warn('No records found in remote GeoFeed.');
            return 0;
        }

        $this->info('Found ' . count($records) . ' records in remote GeoFeed.');
        $this->info('Importing unique locations...');

        $imported = 0;
        $skipped = 0;
        $locations = [];

        // Collect unique locations
        foreach ($records as $record) {
            $countryCode = strtoupper(trim((string) ($record['country_code'] ?? '')));
            if ($countryCode === '') {
                continue;
            }

            $region = trim((string) ($record['region'] ?? '')) ?: null;
            $city = trim((string) ($record['city'] ?? '')) ?: null;
            $postalCode = trim((string) ($record['postal_code'] ?? '')) ?: null;

            $key = implode('|', [$countryCode, $region ?? '', $city ?? '', $postalCode ?? '']);
            
            if (!isset($locations[$key])) {
                $locations[$key] = [
                    'country_code' => $countryCode,
                    'region' => $region,
                    'city' => $city,
                    'postal_code' => $postalCode,
                ];
            }
        }

        $this->info('Found ' . count($locations) . ' unique locations after deduplication.');

        $bar = $this->output->createProgressBar(count($locations));
        $bar->start();

        foreach ($locations as $location) {
            $exists = GeoFeedLocation::query()
                ->where('country_code', $location['country_code'])
                ->where('region', $location['region'])
                ->where('city', $location['city'])
                ->where('postal_code', $location['postal_code'])
                ->exists();

            if ($exists) {
                $skipped++;
            } else {
                GeoFeedLocation::create($location);
                $imported++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Import completed:");
        $this->info("  - Imported: {$imported}");
        $this->info("  - Skipped (already exists): {$skipped}");
        $this->info("  - Total: " . ($imported + $skipped));

        return 0;
    }

    private function importFromLocalAssets(GeoFeedService $service): int
    {
        $this->info('Scanning local IP assets...');

        $assets = \App\Models\IpAsset::query()
            ->whereNotNull('geo_location')
            ->where('geo_location', '!=', '')
            ->get();

        if ($assets->isEmpty()) {
            $this->warn('No IP assets with geo_location found.');
            return 0;
        }

        $this->info('Found ' . $assets->count() . ' assets with geo_location.');
        $this->info('Extracting unique locations...');

        $imported = 0;
        $skipped = 0;
        $locations = [];

        foreach ($assets as $asset) {
            $fields = $service->extractGeoFields($asset->geo_location);
            [$countryCode, $region, $city, $postalCode] = $fields;

            if (!$countryCode) {
                continue;
            }

            $countryCode = strtoupper(trim($countryCode));
            $key = implode('|', [$countryCode, $region ?? '', $city ?? '', $postalCode ?? '']);

            if (!isset($locations[$key])) {
                $locations[$key] = [
                    'country_code' => $countryCode,
                    'region' => $region,
                    'city' => $city,
                    'postal_code' => $postalCode,
                ];
            }
        }

        $this->info('Found ' . count($locations) . ' unique locations after deduplication.');

        $bar = $this->output->createProgressBar(count($locations));
        $bar->start();

        foreach ($locations as $location) {
            $exists = GeoFeedLocation::query()
                ->where('country_code', $location['country_code'])
                ->where('region', $location['region'])
                ->where('city', $location['city'])
                ->where('postal_code', $location['postal_code'])
                ->exists();

            if ($exists) {
                $skipped++;
            } else {
                GeoFeedLocation::create($location);
                $imported++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Import completed:");
        $this->info("  - Imported: {$imported}");
        $this->info("  - Skipped (already exists): {$skipped}");
        $this->info("  - Total: " . ($imported + $skipped));

        return 0;
    }

    private function fetchFromCustomUrl(string $url): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)->get($url);
            
            if (!$response->ok()) {
                return ['status' => 'error', 'records' => [], 'error' => 'HTTP ' . $response->status()];
            }

            $records = $this->parseCsv($response->body());
            return ['status' => 'ok', 'records' => $records, 'error' => null];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'records' => [], 'error' => $e->getMessage()];
        }
    }

    private function parseCsv(string $csv): array
    {
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $csv)));
        if (!$lines) {
            return [];
        }

        $firstLine = reset($lines);
        $firstRow = str_getcsv($firstLine);
        
        // Check if first line is a header (no IP CIDR pattern) or data
        $hasHeader = !preg_match('/^\d+\.\d+\.\d+\.\d+\/\d+$/', trim($firstRow[0] ?? ''));
        
        $header = null;
        $records = [];

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            
            if (!$header) {
                if ($hasHeader) {
                    // First line is header
                    $header = array_map(fn ($value) => strtolower(trim($value)), $row);
                    continue;
                } else {
                    // No header, use standard GeoFeed field order
                    $header = ['ip_prefix', 'country_code', 'region', 'city', 'postal_code'];
                }
            }

            $record = [];
            foreach ($header as $index => $key) {
                $record[$key] = $row[$index] ?? '';
            }

            if (!empty($record['ip_prefix'])) {
                $records[] = $record;
            }
        }

        return $records;
    }
}
