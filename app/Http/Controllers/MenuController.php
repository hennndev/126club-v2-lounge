<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\PosCategorySetting;
use App\Models\Printer;
use App\Services\AccurateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function __construct(protected AccurateService $accurateService) {}

    public function index(): View
    {
        $inventoryItems = InventoryItem::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'unit']);

        $inventoryCategoryTypes = InventoryItem::query()
            ->where('is_active', true)
            ->whereNotNull('category_type')
            ->where('category_type', '!=', '')
            ->distinct()
            ->orderBy('category_type')
            ->pluck('category_type');

        $menuCategoryTypes = PosCategorySetting::query()
            ->where('is_menu', true)
            ->orderBy('category_type')
            ->pluck('category_type');

        $menusByCategory = $menuCategoryTypes
            ->mapWithKeys(function (string $categoryType) {
                $menus = InventoryItem::query()
                    ->with(['printers:id,name,location'])
                    ->where('is_active', true)
                    ->where('category_type', $categoryType)
                    ->orderBy('name')
                    ->get(['id', 'code', 'name', 'category_type', 'price', 'unit', 'include_tax', 'include_service_charge']);

                return [$categoryType => $menus];
            });

        $printers = Printer::query()
            ->active()
            ->orderBy('location')
            ->orderBy('name')
            ->get(['id', 'name', 'location']);

        return view('menus.index', compact('inventoryItems', 'inventoryCategoryTypes', 'menuCategoryTypes', 'menusByCategory', 'printers'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_mode' => ['required', 'string', 'in:manual,auto'],
            'no' => 'nullable|required_if:code_mode,manual|string|max:100',
            'name' => 'required|string|max:255',
            'item_type' => ['required', 'string', 'in:GROUP,INVENTORY'],
            'category_type' => ['required', 'string', 'max:255'],
            'unit' => 'required|string|max:50',
            'selling_price' => 'required|numeric|min:0',
            'include_tax' => ['nullable', 'boolean'],
            'include_service_charge' => ['nullable', 'boolean'],
            'printer_ids' => ['nullable', 'array'],
            'printer_ids.*' => ['integer', 'exists:printers,id'],
            'detail_group' => 'nullable|array',
            'detail_group.*.item_no' => 'required|string|max:100',
            'detail_group.*.detail_name' => 'required|string|max:255',
            'detail_group.*.quantity' => 'required|integer|min:1',
        ], [
            'category_type.required' => 'Kategori menu wajib dipilih.',
        ]);

        $detailGroup = collect($validated['detail_group'] ?? [])
            ->values()
            ->map(fn ($row, $seq) => [
                'itemNo' => $row['item_no'],
                'detailName' => $row['detail_name'],
                'quantity' => (int) $row['quantity'],
            ])
            ->toArray();

        $payload = [
            'name' => $validated['name'],
            'itemType' => $validated['item_type'],
            'unit1Name' => $validated['unit'],
            'unitPrice' => $validated['selling_price'],
            'detailGroup' => $detailGroup,
        ];

        if (($validated['code_mode'] ?? 'manual') === 'manual' && ! empty($validated['no'])) {
            $payload['no'] = $validated['no'];
        }

        if (! empty($validated['category_type'])) {
            $payload['itemCategoryName'] = $validated['category_type'];
        }

        try {
            $result = $this->accurateService->saveItem($payload);

            $accurateId = $result['r']['id'] ?? null;
            $accurateNo = $validated['no']
                ?? $result['r']['no']
                ?? $result['d']['no']
                ?? null;

            if ($accurateId !== null) {
                $inventoryItem = InventoryItem::updateOrCreate(
                    ['accurate_id' => $accurateId],
                    [
                        'accurate_id' => $accurateId,
                        'code' => $accurateNo ?? 'ACC-'.$accurateId,
                        'name' => $validated['name'],
                        'category_type' => $validated['category_type'] ?? '',
                        'price' => $validated['selling_price'],
                        'include_tax' => (bool) ($validated['include_tax'] ?? false),
                        'include_service_charge' => (bool) ($validated['include_service_charge'] ?? false),
                        'stock_quantity' => 0,
                        'unit' => $validated['unit'],
                        'is_active' => true,
                    ]
                );

                $inventoryItem->printers()->sync($validated['printer_ids'] ?? []);
            }

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function updateTaxFlags(Request $request, InventoryItem $inventory): JsonResponse
    {
        $validated = $request->validate([
            'field' => ['required', 'string', 'in:include_tax,include_service_charge'],
            'value' => ['required', 'boolean'],
        ]);

        $inventory->update([$validated['field'] => $validated['value']]);

        return response()->json(['success' => true, 'value' => $inventory->fresh()->{$validated['field']}]);
    }

    public function fetchDetail(InventoryItem $inventory): JsonResponse
    {
        $inventory->loadMissing(['printers:id']);

        if (! $inventory->accurate_id) {
            return response()->json(['success' => false, 'message' => 'Item tidak memiliki Accurate ID.'], 422);
        }

        $detail = $this->accurateService->getDetailItem($inventory->accurate_id);

        if (! $detail) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil detail dari Accurate.'], 502);
        }

        $detailGroup = collect($detail['detailGroup'] ?? [])->map(fn ($group) => [
            'item_id' => $group['itemId'] ?? null,
            'detail_name' => $group['detailName'] ?? null,
            'quantity' => $group['quantity'] ?? 0,
            'unit' => $group['itemUnit']['name'] ?? null,
            'seq' => $group['seq'] ?? null,
        ])->values()->all();

        return response()->json([
            'success' => true,
            'name' => $inventory->name,
            'printer_ids' => $inventory->printers->pluck('id')->values()->all(),
            'detail_group' => $detailGroup,
        ]);
    }

    public function updatePrinterTargets(Request $request, InventoryItem $inventory): JsonResponse
    {
        $validated = $request->validate([
            'printer_ids' => ['nullable', 'array'],
            'printer_ids.*' => ['integer', 'exists:printers,id'],
        ]);

        $inventory->printers()->sync($validated['printer_ids'] ?? []);

        return response()->json([
            'success' => true,
            'printer_ids' => $inventory->printers()->pluck('printers.id')->all(),
        ]);
    }
}
