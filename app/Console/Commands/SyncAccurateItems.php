<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Services\AccurateService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncAccurateItems extends Command
{
    protected $signature = 'accurate:sync-items {--force : Force sync without confirmation}';

    protected $description = 'Sync items data from Accurate to local database';

    protected $accurateService;

    protected $stats = [
        'created' => 0,
        'updated' => 0,
        'failed' => 0,
        'total' => 0,
    ];

    public function __construct(AccurateService $accurateService)
    {
        parent::__construct();
        $this->accurateService = $accurateService;
    }

    public function handle()
    {
        // API token mode: no OAuth session needed, AccurateService handles auth automatically
        if (! config('accurate.api_token')) {
            $accessToken = Cache::get('accurate_access_token') ?? session('accurate_access_token');
            $database = Cache::get('accurate_database') ?? session('accurate_database');

            if (! $accessToken || ! $database) {
                Log::error('Accurate Items Sync: missing access token or database');

                return 1;
            }
        }

        if (! $this->option('force')) {
            if (! $this->confirm('Apakah Anda yakin ingin melakukan sync items?', true)) {
                return 0;
            }
        }
        $startTime = now();
        try {
            DB::beginTransaction();

            $this->syncItems();

            DB::commit();

            return 0;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Accurate Items Sync Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    protected function syncItems()
    {
        $page = 1;
        $pageSize = 100;
        $totalPages = 0;
        do {
            $request = new \Illuminate\Http\Request;
            $request->merge([
                'page' => $page,
                'pageSize' => $pageSize,
            ]);

            $items = $this->accurateService->getItems($request);
            Log::info('Fetched Items page', ['page' => $page, 'count' => $items->count()]);
            if ($items->isEmpty()) {
                break;
            }
            if ($page === 1) {
                $totalPages = ceil($this->stats['total'] / $pageSize);
            }
            foreach ($items as $itemData) {
                try {
                    $this->syncSingleItem($itemData);
                } catch (Exception $e) {
                }
            }
            $page++;
        } while ($items->count() >= $pageSize);
    }

    protected function syncSingleItem(array $itemData)
    {
        $accurateId = $itemData['id'] ?? null;

        if (! $accurateId) {
            $this->stats['failed']++;

            return;
        }
        $itemDetail = $this->accurateService->getDetailItem($accurateId);
        Log::info('Fetched Item detail', ['accurate_id' => $accurateId, 'detail' => $itemDetail]);

        if (! $itemDetail) {
            $this->stats['failed']++;

            return;
        }
        $itemData = $itemDetail;

        $itemDataToSave = [
            'name' => $itemData['name'] ?? 'Unknown Item',
            'code' => $itemData['no'] ?? 'UNKNOWN-'.$accurateId,
            'unit' => $itemData['unit1Name'] ?? 'Unit',
            'category_type' => $itemData['itemCategory']['name'] ?? 'Uncategorized',
            'price' => $itemData['unitPrice'] ?? 0,
            'item_produced' => $itemData['itemProduced'] ?? false,
            'stock_quantity' => $itemData['totalUnit1Quantity'] ?? 0,
            'material_produced' => $itemData['materialProduced'] ?? false,
        ];

        Log::info('Syncing Item', ['accurate_id' => $accurateId, 'data' => $itemDataToSave]);

        $item = InventoryItem::updateOrCreate(
            ['accurate_id' => $accurateId],
            $itemDataToSave
        );
    }
}
