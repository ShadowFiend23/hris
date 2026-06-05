<?php

namespace App\Console\Commands;

use App\Jobs\SyncAlpetaLogsJob;
use Illuminate\Console\Command;

class SyncAlpetaLogs extends Command
{
    protected $signature = 'alpeta:sync
                            {--month= : Month to sync in YYYYMM format (defaults to current month)}';

    protected $description = 'Sync biometric attendance logs from the Alpeta device database';

    public function handle(): int
    {
        $month = $this->option('month') ?? now()->format('Ym');

        if (! preg_match('/^\d{6}$/', $month)) {
            $this->error("Invalid month format: {$month}. Expected YYYYMM (e.g. 202603).");

            return Command::FAILURE;
        }

        $this->info("Dispatching sync job for month: {$month}");

        SyncAlpetaLogsJob::dispatch($month, 'manual');

        $this->info('Job dispatched successfully. Check biometric_sync_logs for progress.');

        return Command::SUCCESS;
    }
}
