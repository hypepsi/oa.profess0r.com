<?php

namespace App\Jobs;

use App\Models\EmailAccount;
use App\Services\ImapService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncEmailAccountJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;
    public int $tries   = 3;

    public function __construct(
        public readonly EmailAccount $account
    ) {}

    public function handle(ImapService $imap): void
    {
        $this->account->update(['sync_status' => 'syncing', 'sync_error' => null]);

        try {
            $count = $imap->syncAccount($this->account, 100);

            $this->account->update([
                'sync_status'    => 'idle',
                'last_synced_at' => now(),
                'sync_error'     => null,
            ]);

            Log::info("Synced {$count} new messages for {$this->account->email}");

        } catch (\Exception $e) {
            $this->account->update([
                'sync_status' => 'error',
                'sync_error'  => $e->getMessage(),
            ]);

            Log::error("Email sync failed for {$this->account->email}: " . $e->getMessage());
            throw $e;
        }
    }
}
