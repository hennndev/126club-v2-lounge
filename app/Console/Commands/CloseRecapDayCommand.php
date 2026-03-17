<?php

namespace App\Console\Commands;

use App\Services\RecapClosingService;
use Illuminate\Console\Command;

class CloseRecapDayCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recap:close-day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close current dashboard totals into recap history and reset dashboard';

    public function __construct(protected RecapClosingService $recapClosingService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $result = $this->recapClosingService->closeDay();

        if ($result['status'] === 'already_closed') {
            $this->warn('Recap for '.$result['end_day'].' has already been closed.');

            return self::SUCCESS;
        }

        if ($result['status'] === 'no_data') {
            $this->line('No dashboard totals to close for '.$result['end_day'].'.');

            return self::SUCCESS;
        }

        $this->info('Recap closing completed for '.$result['end_day'].'.');

        return self::SUCCESS;
    }
}
