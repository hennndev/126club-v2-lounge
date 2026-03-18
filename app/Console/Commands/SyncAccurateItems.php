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

    protected $itemFields = [
        'id', 'name', 'no', 'unit1Name', 'itemCategory', 'unitPrice',
        'itemProduced', 'materialProduced',
    ];

    protected function fetchStockMap(): array
    {
        $map = [];
        $page = 1;
        $pageSize = 500;

        do {
            $request = new \Illuminate\Http\Request;
            $request->merge([
                'page' => $page,
                'pageSize' => $pageSize,
            ]);

            // list-stock.do returns no + unit1Quantity (total stock across warehouses)
            $stocks = $this->accurateService->getStockItems($request);
            Log::info('DATA', ['stocks' => $stocks]);

            if ($stocks->isEmpty()) {
                break;
            }

            foreach ($stocks as $stock) {
                $no = $stock['no'] ?? null;
                if ($no !== null) {
                    $map[$no] = $stock['quantity'] ?? $stock['quantity'] ?? 0;
                }
            }

            $page++;
        } while ($stocks->count() >= $pageSize);

        Log::info('Accurate Stock Map fetched', ['total_items' => count($map)]);

        return $map;
    }

    protected function syncItems()
    {
        // $stockMap = $this->fetchStockMap();

        $page = 1;
        $pageSize = 100;
        do {
            $request = new \Illuminate\Http\Request;
            $request->merge([
                'page' => $page,
                'pageSize' => $pageSize,
            ]);

            $items = $this->accurateService->getItems($request, $this->itemFields);

            if ($items->isEmpty()) {
                break;
            }

            foreach ($items as $itemData) {
                try {
                    $this->syncSingleItem($itemData);
                } catch (Exception $e) {
                    Log::warning('Sync item failed', ['id' => $itemData['id'] ?? null, 'error' => $e->getMessage()]);
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
        $itemNo = $itemData['no'] ?? null;
        $detailGroup = $this->mapDetailGroup($itemData['detailGroup'] ?? []);

        Log::info('items detail', ['detailGroup' => $itemData]);

        $itemDataToSave = [
            'name' => $itemData['name'] ?? 'Unknown Item',
            'code' => $itemNo ?? 'UNKNOWN-'.$accurateId,
            'unit' => $itemData['unit1Name'] ?? 'Unit',
            'category_type' => $itemData['itemCategory']['name'] ?? 'Uncategorized',
            'price' => $itemData['unitPrice'] ?? 0,
            'stock_quantity' => $itemData['allQuantity'] ?? 0,
            'is_active' => ($itemData['suspended'] ?? false) === false,
            'detail_group' => $detailGroup,
        ];

        InventoryItem::updateOrCreate(
            ['accurate_id' => $accurateId],
            $itemDataToSave
        );
    }

    protected function mapDetailGroup(array $detailGroup): array
    {
        return collect($detailGroup)
            ->map(function (array $detail): array {
                return [
                    'accurate_id' => $detail['id'] ?? null,
                    'name' => $detail['detailName'] ?? null,
                    'quantity' => $detail['quantity'] ?? 0,
                ];
            })
            ->values()
            ->all();
    }
}
