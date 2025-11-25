<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CleanOldActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity-logs:clean {--days=90 : Number of days to keep logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean activity logs older than specified days (default: 90 days)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now('Asia/Shanghai')->subDays($days);

        $this->info("Cleaning activity logs older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})...");

        $deletedCount = ActivityLog::where('created_at', '<', $cutoffDate)->delete();

        $this->info("Successfully deleted {$deletedCount} old activity log(s).");

        return Command::SUCCESS;
    }
}
