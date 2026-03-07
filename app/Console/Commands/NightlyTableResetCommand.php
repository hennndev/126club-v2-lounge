<?php

namespace App\Console\Commands;

use App\Jobs\NightlyTableReset;
use Illuminate\Console\Command;

class NightlyTableResetCommand extends Command
{
    protected $signature = 'tables:nightly-reset';

    protected $description = 'Reset all tables to available and force-close unpaid billings (nightly reset)';

    public function handle(): void
    {
        $this->info('Running nightly table reset...');
        dispatch_sync(new NightlyTableReset);
        $this->info('Done. Check storage/logs/laravel.log for details.');
    }
}
