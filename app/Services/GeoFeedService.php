<?php

namespace App\Services;

use App\Models\IpAsset;
use App\Models\GeoFeedLocation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GeoFeedService
{
    private static ?array $remoteIndexCache = null;

    public function buildCsv(): string
    {
        $rows = [];
        $rows[] = ['ip_prefix', 'country_code', 'region', 'city', 'postal_code'];

        $assets = IpAsset::query()
            ->select(['cidr', 'geo_location', 'status'])
            ->orderBy('cidr')
            ->get();

        foreach ($assets as $asset) {
            if (!is_string($asset->cidr) || trim($asset->cidr) === '') {
                continue;
            }

            $normalized = $this->normalizeGeoLocation($asset->geo_location);
            [$country, $region, $city, $postal] = $this->parseGeoLocation($normalized);

            $rows[] = [
                $asset->cidr,
                $country ?? '',
                $region ?? '',
                $city ?? '',
                $postal ?? '',
            ];
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv ?: '';
    }

    public function writeLocal(string $filename = 'geofeed.test.csv'): string
    {
        $path = 'geofeed/' . $filename;
        Storage::disk('local')->put($path, $this->buildCsv());

        return storage_path('app/' . $path);
    }

    public function uploadTestFeed(string $filename = 'geofeed.test.csv'): array
    {
        $csv = $this->buildCsv();
        $localPath = $this->writeLocal($filename);

        $method = config('geofeed.upload_method');
        $result = ['uploaded' => false, 'method' => $method, 'local_path' => $localPath, 'message' => '', 'filename' => $filename];

        if ($method === 'ftp') {
            return $this->uploadViaFtp($csv, $result, $filename);
        }

        if ($method === 'http') {
            return $this->uploadViaHttp($csv, $result, $filename);
        }

        $result['message'] = 'Unsupported upload method. Use http or ftp.';
        return $result;
    }

    public function getRemoteIndex(): array
    {
        if (self::$remoteIndexCache !== null) {
            return self::$remoteIndexCache;
        }

        $cacheKey = 'geofeed.remote.index';
        $ttl = config('geofeed.cache_seconds', 600);

        self::$remoteIndexCache = Cache::remember($cacheKey, $ttl, function () {
            $url = config('geofeed.remote_url');
            if (!$url) {
                return ['status' => 'missing_url', 'records' => [], 'error' => 'Remote URL not configured.'];
            }

            try {
                $requestUrl = $url . (str_contains($url, '?') ? '&' : '?') . 't=' . time();
                $response = Http::timeout(8)->get($requestUrl);
                if (!$response->ok()) {
                    return ['status' => 'http_error', 'records' => [], 'error' => 'Remote fetch failed.'];
                }

                $records = $this->parseCsv($response->body());
                return ['status' => 'ok', 'records' => $records, 'error' => null];
            } catch (\Throwable $e) {
                return ['status' => 'exception', 'records' => [], 'error' => $e->getMessage()];
            }
        });

        return self::$remoteIndexCache;
    }

    public function syncLocationsFromRemote(): int
    {
        $remote = $this->getRemoteIndex();
        if (($remote['status'] ?? 'error') !== 'ok') {
            return 0;
        }

        $created = 0;
        foreach ($remote['records'] as $record) {
            $created += $this->upsertLocation([
                'country_code' => $record['country_code'] ?? '',
                'region' => $record['region'] ?? null,
                'city' => $record['city'] ?? null,
                'postal_code' => $record['postal_code'] ?? null,
            ]);
        }

        return $created;
    }

    public function ensureLocationsLoaded(): void
    {
        if (GeoFeedLocation::query()->exists()) {
            return;
        }

        $cacheKey = 'geofeed.locations.bootstrap';
        if (Cache::get($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, 300);
        $this->syncLocationsFromRemote();
    }

    public function getRemoteMatchForCidr(?string $cidr): ?array
    {
        if (!$cidr || !str_contains($cidr, '/')) {
            return null;
        }

        if (!$this->isIpv4Cidr($cidr)) {
            return ['status' => 'unsupported'];
        }

        $remote = $this->getRemoteIndex();
        if (($remote['status'] ?? 'error') !== 'ok') {
            return ['status' => 'unavailable'];
        }

        $best = null;
        foreach ($remote['records'] as $record) {
            $prefix = $record['ip_prefix'] ?? '';
            if (!$this->isIpv4Cidr($prefix)) {
                continue;
            }

            if ($this->cidrContains($prefix, $cidr)) {
                $currentMask = (int) explode('/', $cidr)[1];
                $prefixMask = (int) explode('/', $prefix)[1];
                if (!$best || $prefixMask >= $currentMask) {
                    $best = $record + ['status' => 'matched'];
                }
            }
        }

        return $best ?: ['status' => 'missing'];
    }

    public function buildLocalGeoFields(?string $geoLocation): array
    {
        [$country, $region, $city, $postal] = $this->parseGeoLocation($geoLocation);

        return [
            'country_code' => $country ?? '',
            'region' => $region ?? '',
            'city' => $city ?? '',
            'postal_code' => $postal ?? '',
        ];
    }

    public function formatGeoLabel(array $fields): string
    {
        $parts = array_filter([
            $fields['country_code'] ?? null,
            $fields['region'] ?? null,
            $fields['city'] ?? null,
            $fields['postal_code'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        return $parts ? implode(' / ', $parts) : '—';
    }

    public function normalizeGeoLocation(?string $geoLocation): string
    {
        $geoLocation = trim((string) $geoLocation);
        if ($geoLocation === '') {
            return '';
        }

        $normalized = preg_replace('/\s+/', ' ', $geoLocation) ?? '';

        $aliases = [
            'HK' => 'HK, Hong Kong',
            'HONGKONG' => 'HK, Hong Kong',
            'US-LA' => 'US, CA, Los Angeles',
            'US-LAX' => 'US, CA, Los Angeles',
            'US-NY' => 'US, NY, New York',
            'US-NYC' => 'US, NY, New York',
            'SG' => 'SG, Singapore',
            'TOKYO' => 'JP, Tokyo',
        ];

        $aliasKey = strtoupper(str_replace([' ', '_'], '-', $normalized));
        if (isset($aliases[$aliasKey])) {
            return $aliases[$aliasKey];
        }

        if (str_contains($normalized, ',')) {
            $parts = array_map('trim', explode(',', $normalized));
            $country = $this->normalizeCountryCode($parts[0] ?? '');
            if ($country) {
                $rest = array_values(array_filter(array_slice($parts, 1), fn ($value) => $value !== ''));
                return trim(implode(', ', array_merge([$country], $rest)));
            }
        }

        return $normalized;
    }

    public function extractGeoFields(?string $geoLocation): array
    {
        return $this->parseGeoLocation($geoLocation);
    }

    public function isGeoSynced(array $local, array $remote): bool
    {
        $keys = ['country_code', 'region', 'city', 'postal_code'];

        foreach ($keys as $key) {
            $left = strtolower(trim((string) ($local[$key] ?? '')));
            $right = strtolower(trim((string) ($remote[$key] ?? '')));

            if ($left !== $right) {
                return false;
            }
        }

        return true;
    }

    private function uploadViaHttp(string $csv, array $result, string $filename = 'geofeed.test.csv'): array
    {
        $baseUrl = config('geofeed.upload_url');
        $token = config('geofeed.upload_token');

        if (!$baseUrl) {
            $result['message'] = 'Upload URL not configured.';
            return $result;
        }

        // Determine upload URL based on filename
        if ($filename === 'geofeed.csv') {
            // Production mode: upload to geofeed-upload.php (will write to geofeed.csv)
            $url = str_replace('geofeed.test.csv', 'geofeed.csv', $baseUrl);
            $url = str_replace('geofeed-upload.php', 'geofeed-upload-prod.php', $url);
        } else {
            // Test mode: use default URL
            $url = $baseUrl;
        }

        try {
            $request = Http::timeout(15);
            if ($token) {
                $request = $request->withHeaders(['Authorization' => 'Bearer ' . $token]);
            }

            $response = $request->withBody($csv, 'text/csv')->put($url);
            if (!$response->ok()) {
                $result['message'] = 'Remote upload failed. HTTP ' . $response->status();
                return $result;
            }

            Cache::forget('geofeed.remote.index');
            self::$remoteIndexCache = null;

            $result['uploaded'] = true;
            $result['message'] = 'Uploaded via HTTP to ' . $filename;
            $result['target_url'] = $url;
            return $result;
        } catch (\Throwable $e) {
            $result['message'] = $e->getMessage();
            return $result;
        }
    }

    private function uploadViaFtp(string $csv, array $result, string $filename = 'geofeed.test.csv'): array
    {
        $host = config('geofeed.ftp_host');
        $user = config('geofeed.ftp_user');
        $pass = config('geofeed.ftp_pass');
        $port = config('geofeed.ftp_port');
        $ssl = config('geofeed.ftp_ssl');
        $basePath = config('geofeed.ftp_path', '/geofeed.test.csv');
        $timeout = config('geofeed.ftp_timeout', 15);

        // Determine FTP path based on filename
        $path = $filename === 'geofeed.csv' 
            ? str_replace('geofeed.test.csv', 'geofeed.csv', $basePath)
            : $basePath;

        if (!$host || !$user || !$pass) {
            $result['message'] = 'FTP credentials not configured.';
            return $result;
        }

        $connection = $ssl ? @ftp_ssl_connect($host, $port, $timeout) : @ftp_connect($host, $port, $timeout);
        if (!$connection) {
            $result['message'] = 'FTP connection failed.';
            return $result;
        }

        if (!@ftp_login($connection, $user, $pass)) {
            ftp_close($connection);
            $result['message'] = 'FTP login failed.';
            return $result;
        }

        ftp_pasv($connection, true);

        $temp = fopen('php://temp', 'r+');
        fwrite($temp, $csv);
        rewind($temp);

        $success = @ftp_fput($connection, $path, $temp, FTP_BINARY);
        fclose($temp);
        ftp_close($connection);

        if (!$success) {
            $result['message'] = 'FTP upload failed to ' . $path;
            return $result;
        }

        Cache::forget('geofeed.remote.index');
        self::$remoteIndexCache = null;

        $result['uploaded'] = true;
        $result['message'] = 'Uploaded via FTP to ' . $filename;
        $result['target_path'] = $path;
        return $result;
    }

    private function parseCsv(string $csv): array
    {
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $csv)));
        if (!$lines) {
            return [];
        }

        $header = null;
        $records = [];

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (!$header) {
                $header = array_map(fn ($value) => strtolower(trim($value)), $row);
                continue;
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

    private function parseGeoLocation(?string $geoLocation): array
    {
        $geoLocation = trim((string) $geoLocation);
        if ($geoLocation === '') {
            return [null, null, null, null];
        }

        $geoLocation = preg_replace('/\s+/', ' ', $geoLocation);

        $parts = array_map('trim', preg_split('/\s*,\s*/', $geoLocation));
        if (count($parts) >= 2) {
            $country = $this->normalizeCountryCode($parts[0]);
            $region = $parts[1] ?? null;
            $city = $parts[2] ?? null;
            $postal = $parts[3] ?? null;

            return [$country, $region, $city, $postal];
        }

        $dashParts = array_map('trim', preg_split('/\s*-\s*/', $geoLocation));
        if (count($dashParts) >= 2) {
            $country = $this->normalizeCountryCode($dashParts[0]);
            $city = $dashParts[1] ?? null;

            return [$country, null, $city, null];
        }

        $spaceParts = array_values(array_filter(explode(' ', $geoLocation)));
        if (count($spaceParts) >= 2) {
            $country = $this->normalizeCountryCode($spaceParts[0]);
            $city = implode(' ', array_slice($spaceParts, 1));

            return [$country, null, $city, null];
        }

        $country = $this->normalizeCountryCode($geoLocation);
        if ($country) {
            $city = $country === 'HK' ? 'Hong Kong' : null;
            return [$country, null, $city, null];
        }

        return [null, null, $geoLocation, null];
    }

    private function upsertLocation(array $fields): int
    {
        $country = strtoupper(trim((string) ($fields['country_code'] ?? '')));
        if ($country === '') {
            return 0;
        }

        $region = $fields['region'] ?: null;
        $city = $fields['city'] ?: null;
        $postal = $fields['postal_code'] ?: null;

        $exists = GeoFeedLocation::query()
            ->where('country_code', $country)
            ->where('region', $region)
            ->where('city', $city)
            ->where('postal_code', $postal)
            ->exists();

        if ($exists) {
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

    private function normalizeCountryCode(string $value): ?string
    {
        $value = strtoupper(trim($value));
        $value = preg_replace('/[^A-Z]/', '', $value) ?? '';

        $map = [
            'HK' => 'HK',
            'HONGKONG' => 'HK',
            'CN' => 'CN',
            'CHINA' => 'CN',
            'US' => 'US',
            'USA' => 'US',
            'UNITEDSTATES' => 'US',
            'JP' => 'JP',
            'JAPAN' => 'JP',
            'SG' => 'SG',
            'SINGAPORE' => 'SG',
            'KR' => 'KR',
            'KOREA' => 'KR',
            'UK' => 'GB',
            'GB' => 'GB',
            'GERMANY' => 'DE',
            'DE' => 'DE',
            'FRANCE' => 'FR',
            'FR' => 'FR',
        ];

        return $map[$value] ?? (strlen($value) === 2 ? $value : null);
    }

    private function isIpv4Cidr(string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return false;
        }

        [$ip, $mask] = explode('/', $cidr) + [null, null];
        if (!$ip || !is_numeric($mask)) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    private function cidrContains(string $container, string $target): bool
    {
        [$containerIp, $containerMask] = explode('/', $container) + [null, null];
        [$targetIp, $targetMask] = explode('/', $target) + [null, null];
        if (!$containerIp || !$targetIp || !is_numeric($containerMask) || !is_numeric($targetMask)) {
            return false;
        }

        $containerMask = (int) $containerMask;
        $targetMask = (int) $targetMask;

        $containerLong = ip2long($containerIp);
        $targetLong = ip2long($targetIp);
        if ($containerLong === false || $targetLong === false) {
            return false;
        }

        $mask = -1 << (32 - $containerMask);
        $containerNet = $containerLong & $mask;
        $targetNet = $targetLong & $mask;

        return $containerNet === $targetNet && $containerMask <= $targetMask;
    }
}
