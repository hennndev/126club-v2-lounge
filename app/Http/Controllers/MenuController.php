<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\PosCategorySetting;
use App\Services\AccurateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function __construct(protected AccurateService $accurateService) {}

    public function index(): View
    {
        $inventoryItems = InventoryItem::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'unit']);

        $menuCategoryTypes = PosCategorySetting::query()
            ->where('is_menu', true)
            ->orderBy('category_type')
            ->pluck('category_type');

        $menusByCategory = $menuCategoryTypes
            ->mapWithKeys(function (string $categoryType) {
                $menus = InventoryItem::query()
                    ->where('is_active', true)
                    ->where('category_type', $categoryType)
                    ->orderBy('name')
                    ->get(['id', 'code', 'name', 'category_type', 'price', 'unit']);

                return [$categoryType => $menus];
            });

        return view('menus.index', compact('inventoryItems', 'menuCategoryTypes', 'menusByCategory'));
    }

    public function store(Request $request): JsonResponse
    {
        $menuCategoryTypes = PosCategorySetting::query()
            ->where('is_menu', true)
            ->pluck('category_type')
            ->all();

        if ($menuCategoryTypes === []) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada kategori yang ditandai sebagai menu di pengaturan POS.',
            ], 422);
        }

        $validated = $request->validate([
            'no' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'category_type' => ['required', 'string', 'max:255', Rule::in($menuCategoryTypes)],
            'unit' => 'required|string|max:50',
            'selling_price' => 'required|numeric|min:0',
            'detail_group' => 'nullable|array',
            'detail_group.*.item_no' => 'required|string|max:100',
            'detail_group.*.detail_name' => 'required|string|max:255',
            'detail_group.*.quantity' => 'required|integer|min:1',
        ], [
            'category_type.required' => 'Kategori menu wajib dipilih.',
            'category_type.in' => 'Kategori yang dipilih belum ditandai sebagai menu di pengaturan POS.',
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
            'no' => $validated['no'],
            'name' => $validated['name'],
            'itemType' => 'GROUP',
            'unit1Name' => $validated['unit'],
            'unitPrice' => $validated['selling_price'],
            'detailGroup' => $detailGroup,
        ];

        if (! empty($validated['category_type'])) {
            $payload['itemCategoryName'] = $validated['category_type'];
        }

        try {
            $result = $this->accurateService->saveItem($payload);

            $accurateId = $result['r']['id'] ?? null;

            if ($accurateId !== null) {
                InventoryItem::updateOrCreate(
                    ['code' => $validated['no']],
                    [
                        'accurate_id' => $accurateId,
                        'name' => $validated['name'],
                        'category_type' => $validated['category_type'] ?? '',
                        'price' => $validated['selling_price'],
                        'stock_quantity' => 0,
                        'unit' => $validated['unit'],
                        'is_active' => true,
                    ]
                );
            }

            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function fetchDetail(InventoryItem $inventory): JsonResponse
    {
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
            'detail_group' => $detailGroup,
        ]);
    }
}
