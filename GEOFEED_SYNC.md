# GeoFeed Auto-Sync System

## Overview

The GeoFeed auto-sync system automatically synchronizes IP asset geo-location data from OA database to the remote GeoFeed CSV file on `bunnycommunications.com`.

## Features

### 1. Automated Daily Sync
- **Schedule**: Every day at **3:05 AM** (Asia/Shanghai timezone)
- **Mode**: Test mode by default (`geofeed.test.csv`)
- **Process**: Automatically builds CSV from database and uploads to remote server

### 2. Manual Sync from Dashboard
- **Test Sync**: Updates `geofeed.test.csv` (safe for testing)
- **Production Sync**: Updates `geofeed.csv` (admin only, requires confirmation)
- **Download**: Download current GeoFeed data from OA database

### 3. Backup Integration
- GeoFeed sync status displayed on System Backup widget
- Shows last sync time, mode, target file, and remote URL

## Usage Scenarios

### Daily Operations
1. Employee logs into OA
2. Updates IP asset:
   - Change client (e.g., from Customer A to Customer B)
   - Change location (e.g., from Los Angeles to Frankfurt)
   - Change geo_location (e.g., from `US, US-CA, Los Angeles` to `DE, DE-HE, Frankfurt`)
3. System automatically syncs to remote at 3:05 AM next day
4. Remote GeoFeed is updated, no manual intervention needed

### Manual Sync (Testing)
1. Go to Dashboard
2. Click **"Sync Test"** button
3. Uploads to `geofeed.test.csv` immediately
4. Verify at: `https://bunnycommunications.com/geofeed.test.csv`

### Production Deployment (Admin Only)
1. Test thoroughly with test mode first
2. Go to Dashboard (admin account required)
3. Click **"Sync Production"** button
4. Confirm the warning dialog
5. Uploads to `geofeed.csv` (production file)

## How to Switch to Production Mode

### Method 1: Dashboard Manual Sync (Recommended)
- Use the **"Sync Production"** button on Dashboard
- One-time action when you're ready
- No code changes required

### Method 2: Enable Auto-Sync to Production
Edit `/var/www/oa/routes/console.php`:

```php
// Comment out test mode
// Schedule::command('geofeed:sync-remote --mode=test')
//     ->dailyAt('03:05')
//     ->name('geofeed-sync-test')
//     ->withoutOverlapping();

// Uncomment production mode
Schedule::command('geofeed:sync-remote --mode=production')
    ->dailyAt('03:05')
    ->name('geofeed-sync-production')
    ->withoutOverlapping();
```

Then restart cron: `php artisan schedule:work` (or let system cron handle it)

## Command Line Usage

### Sync to Test
```bash
php artisan geofeed:sync-remote --mode=test
```

### Sync to Production
```bash
php artisan geofeed:sync-remote --mode=production
```

### View Schedule
```bash
php artisan schedule:list
```

### Test Sync Manually
```bash
php artisan schedule:test
```

## File Locations

### OA Server
- **Local CSV**: `/var/www/oa/storage/app/geofeed/geofeed.test.csv`
- **Sync Metadata**: `/var/www/oa/storage/app/geofeed/.last_sync.json`

### Remote Server (bunnycommunications.com)
- **Test File**: `/var/www/html/wordpress/geofeed.test.csv`
- **Production File**: `/var/www/html/wordpress/geofeed.csv`
- **Upload Endpoints**:
  - Test: `https://bunnycommunications.com/geofeed-upload.php`
  - Production: `https://bunnycommunications.com/geofeed-upload-prod.php`

## Configuration

Located in `/var/www/oa/config/geofeed.php`:

```php
'upload_method' => 'http',
'upload_url' => 'https://bunnycommunications.com/geofeed-upload.php?token=xxx',
'cache_seconds' => 600, // 10 minutes
```

## Security

- Token-based authentication for uploads
- Production sync requires admin account
- Confirmation dialog for production mode
- Automatic backup retention (30 days)

## Troubleshooting

### Sync Failed
1. Check upload URL configuration
2. Verify token is correct
3. Check remote server accessibility
4. Review `/var/www/oa/storage/logs/laravel.log`

### Production File Not Updated
1. Ensure production upload script is deployed
2. Check file permissions on remote server
3. Verify upload endpoint URL is correct

### Schedule Not Running
1. Ensure system cron is configured
2. Check `php artisan schedule:list`
3. Run manually: `php artisan schedule:run`

## Best Practices

1. **Always test first**: Use test mode to verify changes
2. **Monitor dashboard**: Check sync status regularly
3. **Review before production**: Download and verify CSV before production sync
4. **Keep backups**: System backup includes GeoFeed data
5. **Document changes**: Use Activity Logs to track IP asset modifications

## Support

For issues or questions, contact system administrator.
