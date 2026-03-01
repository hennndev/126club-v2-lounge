<?php

namespace App\Console\Commands;

use App\Models\BomRecipe;
use App\Models\BomRecipeItem;
use App\Models\InventoryItem;
use App\Services\AccurateService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncAccurateBom extends Command
{
    protected $signature = 'accurate:sync-bom {--force : Force sync without confirmation}';

    protected $description = 'Sync Bill of Materials data from Accurate to local database';

    protected AccurateService $accurateService;

    protected array $stats = [
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

    public function handle(): int
    {
        // API token mode: no OAuth session needed, AccurateService handles auth automatically
        if (! config('accurate.api_token')) {
            $accessToken = Cache::get('accurate_access_token') ?? session('accurate_access_token');
            $database = Cache::get('accurate_database') ?? session('accurate_database');

            if (! $accessToken || ! $database) {
                Log::error('Accurate BOM Sync: missing access token or database');

                return 1;
            }
        }

        if (! $this->option('force')) {
            if (! $this->confirm('Apakah Anda yakin ingin melakukan sync BOM?', true)) {
                return 0;
            }
        }

        try {
            DB::beginTransaction();
            $this->syncBoms();
            DB::commit();

            Log::info('Accurate BOM Sync completed', $this->stats);

            return 0;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Accurate BOM Sync Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    protected function syncBoms(): void
    {
        $page = 1;
        $pageSize = 100;

        do {
            $request = new \Illuminate\Http\Request;
            $request->merge(['page' => $page, 'pageSize' => $pageSize]);

            $boms = $this->accurateService->getBillOfMaterials($request);

            Log::info('Fetched BOM page', ['page' => $page, 'count' => $boms->count()]);

            if ($boms->isEmpty()) {
                break;
            }

            foreach ($boms as $bomData) {
                try {
                    $this->syncSingleBom($bomData);
                } catch (Exception $e) {
                    $this->stats['failed']++;
                    Log::warning('Failed to sync single BOM', [
                        'data' => $bomData,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $page++;
        } while ($boms->count() >= $pageSize);
    }

    protected function syncSingleBom(array $bomData): void
    {
        $accurateId = $bomData['id'] ?? null;

        if (! $accurateId) {
            $this->stats['failed']++;

            return;
        }

        $detail = $this->accurateService->getDetailBillOfMaterial($accurateId);

        if (! $detail) {
            $this->stats['failed']++;

            return;
        }

        $this->stats['total']++;

        // Resolve the finished-goods inventory item
        $itemNo = $detail['item']['no'] ?? ($detail['itemNo'] ?? null);
        $inventoryItem = $itemNo
            ? InventoryItem::where('code', $itemNo)->first()
            : null;

        if (! $inventoryItem) {
            Log::warning('BOM Sync: InventoryItem not found', ['itemNo' => $itemNo, 'accurateId' => $accurateId]);
            $this->stats['failed']++;

            return;
        }

        // Determine type from inventory item's category
        $categoryType = strtolower($inventoryItem->category_type ?? '');
        $type = in_array($categoryType, ['food']) ? 'food' : 'beverage';

        $recipe = BomRecipe::updateOrCreate(
            ['accurate_id' => $accurateId],
            [
                'inventory_item_id' => $inventoryItem->id,
                'quantity' => $detail['quantity'] ?? 1,
                'type' => $type,
                'description' => $detail['description'] ?? null,
                'selling_price' => $inventoryItem->price ?? 0,
                'total_cost' => 0,
                'is_active' => true,
            ]
        );

        $wasRecentlyCreated = $recipe->wasRecentlyCreated;

        // Sync ingredients (detailMaterial)
        $recipe->items()->delete();

        $totalCost = 0;
        foreach ($detail['detailMaterial'] ?? [] as $materialRow) {
            $materialNo = $materialRow['item']['no'] ?? ($materialRow['itemNo'] ?? null);
            $materialItem = $materialNo
                ? InventoryItem::where('code', $materialNo)->first()
                : null;

            if (! $materialItem) {
                continue;
            }

            $qty = $materialRow['quantity'] ?? 1;
            $cost = $materialItem->price * $qty;

            BomRecipeItem::create([
                'bom_recipe_id' => $recipe->id,
                'inventory_item_id' => $materialItem->id,
                'quantity' => $qty,
                'unit' => $materialItem->unit,
                'cost' => $cost,
            ]);

            $totalCost += $cost;
        }

        $recipe->update(['total_cost' => $totalCost]);

        if ($wasRecentlyCreated) {
            $this->stats['created']++;
        } else {
            $this->stats['updated']++;
        }
    }
}
