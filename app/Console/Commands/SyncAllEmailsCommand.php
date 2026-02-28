<?php

namespace App\Console\Commands;

use App\Jobs\SyncEmailAccountJob;
use App\Models\EmailAccount;
use Illuminate\Console\Command;

class SyncAllEmailsCommand extends Command
{
    protected $signature   = 'email:sync-all';
    protected $description = 'Dispatch sync jobs for all active email accounts';

    public function handle(): void
    {
        $accounts = EmailAccount::where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            $this->info('No active email accounts found.');
            return;
        }

        foreach ($accounts as $account) {
            SyncEmailAccountJob::dispatch($account);
            $this->line("Queued sync for: {$account->email}");
        }

        $this->info("Dispatched {$accounts->count()} sync job(s).");
    }
}
