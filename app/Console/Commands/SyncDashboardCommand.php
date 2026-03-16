<?php

namespace App\Console\Commands;

use App\Services\DashboardSyncService;
use Illuminate\Console\Command;

class SyncDashboardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync dashboard aggregates for today only';

    public function __construct(protected DashboardSyncService $dashboardSyncService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dashboard = $this->dashboardSyncService->sync();

        $this->info('Dashboard synced successfully (today only).');
        $this->line('Total Amount: Rp '.number_format((float) $dashboard->total_amount, 0, ',', '.'));
        $this->line('Total Transactions: '.(int) $dashboard->total_transactions);

        return self::SUCCESS;
    }
}
