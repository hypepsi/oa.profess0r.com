<?php

return [
    'remote_url' => env('GEOFEED_REMOTE_URL', 'https://bunnycommunications.com/geofeed.test.csv'),
    'cache_seconds' => (int) env('GEOFEED_CACHE_SECONDS', 600),

    // Upload configuration (choose one method)
    'upload_method' => env('GEOFEED_UPLOAD_METHOD', 'http'), // http | ftp
    'upload_url' => env('GEOFEED_UPLOAD_URL', ''),
    'upload_token' => env('GEOFEED_UPLOAD_TOKEN', ''),

    'ftp_host' => env('GEOFEED_FTP_HOST', ''),
    'ftp_user' => env('GEOFEED_FTP_USER', ''),
    'ftp_pass' => env('GEOFEED_FTP_PASS', ''),
    'ftp_port' => (int) env('GEOFEED_FTP_PORT', 21),
    'ftp_ssl' => (bool) env('GEOFEED_FTP_SSL', false),
    'ftp_path' => env('GEOFEED_FTP_PATH', '/geofeed.test.csv'),
    'ftp_timeout' => (int) env('GEOFEED_FTP_TIMEOUT', 15),
];
