<?php

namespace App\Console\Commands;

use App\Models\GeoFeedLocation;
use App\Models\IpAsset;
use App\Services\GeoFeedService;
use Illuminate\Console\Command;

class SyncGeoFeedLocations extends Command
{
    protected $signature = 'geofeed:sync-locations';
    protected $description = 'Sync GeoFeed locations from remote geofeed and local IP assets';

    public function handle(GeoFeedService $service): int
    {
        $created = 0;

        $remote = $service->getRemoteIndex();
        if (($remote['status'] ?? 'error') === 'ok') {
            foreach ($remote['records'] as $record) {
                $created += $this->upsertRecord([
                    'country_code' => $record['country_code'] ?? '',
                    'region' => $record['region'] ?? null,
                    'city' => $record['city'] ?? null,
                    'postal_code' => $record['postal_code'] ?? null,
                ]);
            }
        }

        $localAssets = IpAsset::query()
            ->select(['geo_location'])
            ->whereNotNull('geo_location')
            ->get();

        foreach ($localAssets as $asset) {
            $normalized = $service->normalizeGeoLocation($asset->geo_location);
            [$country, $region, $city, $postal] = $service->extractGeoFields($normalized);
            if (!$country && !$region && !$city && !$postal) {
                continue;
            }

            $created += $this->upsertRecord([
                'country_code' => $country ?? '',
                'region' => $region,
                'city' => $city,
                'postal_code' => $postal,
            ]);
        }

        $this->info('GeoFeed locations synced.');
        $this->line('New records: ' . $created);

        return 0;
    }

    private function upsertRecord(array $fields): int
    {
        $country = strtoupper(trim((string) ($fields['country_code'] ?? '')));
        if ($country === '') {
            return 0;
        }

        $region = $fields['region'] ?: null;
        $city = $fields['city'] ?: null;
        $postal = $fields['postal_code'] ?: null;

        $existing = GeoFeedLocation::query()
            ->where('country_code', $country)
            ->where('region', $region)
            ->where('city', $city)
            ->where('postal_code', $postal)
            ->first();

        if ($existing) {
            return 0;
        }

        GeoFeedLocation::create([
            'country_code' => $country,
            'region' => $region,
            'city' => $city,
            'postal_code' => $postal,
        ]);

        return 1;
    }

}
