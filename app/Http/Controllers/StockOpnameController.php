<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public function index(): View
    {
        $items = InventoryItem::where('is_active', true)
            ->orderBy('name')
            ->get();

        $categoryTypes = $items->pluck('category_type')->unique()->sort()->values();

        $itemsData = $items->map(fn ($item) => [
            'inventory_item_id' => $item->id,
            'name' => $item->name,
            'category_type' => $item->category_type,
            'unit' => $item->unit,
            'system_stock' => $item->stock_quantity,
            'physical_stock' => '',
            'item_notes' => '',
        ])->values()->all();

        return view('stock-opname.index', compact('items', 'categoryTypes', 'itemsData'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opname_date' => ['required', 'date'],
            'officer_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.system_stock' => ['required', 'integer', 'min:0'],
            'items.*.physical_stock' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $opname = StockOpname::create([
            'opname_date' => $validated['opname_date'],
            'officer_name' => $validated['officer_name'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
        ]);

        foreach ($validated['items'] as $itemData) {
            StockOpnameItem::create([
                'stock_opname_id' => $opname->id,
                'inventory_item_id' => $itemData['inventory_item_id'],
                'system_stock' => $itemData['system_stock'],
                'physical_stock' => $itemData['physical_stock'] ?? null,
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        return response()->json(['success' => true, 'id' => $opname->id, 'message' => 'Draft berhasil disimpan.']);
    }

    public function update(Request $request, StockOpname $stockOpname): JsonResponse
    {
        if ($stockOpname->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'Stock opname sudah diselesaikan.'], 422);
        }

        $validated = $request->validate([
            'opname_date' => ['required', 'date'],
            'officer_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.system_stock' => ['required', 'integer', 'min:0'],
            'items.*.physical_stock' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $stockOpname->update([
            'opname_date' => $validated['opname_date'],
            'officer_name' => $validated['officer_name'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $stockOpname->items()->delete();

        foreach ($validated['items'] as $itemData) {
            StockOpnameItem::create([
                'stock_opname_id' => $stockOpname->id,
                'inventory_item_id' => $itemData['inventory_item_id'],
                'system_stock' => $itemData['system_stock'],
                'physical_stock' => $itemData['physical_stock'] ?? null,
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Draft berhasil diperbarui.']);
    }

    public function complete(Request $request, StockOpname $stockOpname): JsonResponse
    {
        if ($stockOpname->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'Stock opname sudah diselesaikan.'], 422);
        }

        // First save/update everything
        $validated = $request->validate([
            'opname_date' => ['required', 'date'],
            'officer_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.system_stock' => ['required', 'integer', 'min:0'],
            'items.*.physical_stock' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $stockOpname->update([
            'opname_date' => $validated['opname_date'],
            'officer_name' => $validated['officer_name'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'completed',
            'adjusted_by' => auth()->id(),
            'adjusted_at' => now('Asia/Jakarta'),
        ]);

        $stockOpname->items()->delete();

        foreach ($validated['items'] as $itemData) {
            $physicalStock = $itemData['physical_stock'] ?? null;

            StockOpnameItem::create([
                'stock_opname_id' => $stockOpname->id,
                'inventory_item_id' => $itemData['inventory_item_id'],
                'system_stock' => $itemData['system_stock'],
                'physical_stock' => $physicalStock,
                'notes' => $itemData['notes'] ?? null,
            ]);

            // Adjust inventory stock if physical stock was entered
            if ($physicalStock !== null) {
                InventoryItem::where('id', $itemData['inventory_item_id'])
                    ->update(['stock_quantity' => $physicalStock]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Stock opname selesai. Stok telah disesuaikan.']);
    }

    public function history(): View
    {
        $opnames = StockOpname::withCount('items')
            ->with('adjustedBy')
            ->orderByDesc('opname_date')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('stock-opname.history', compact('opnames'));
    }

    public function show(StockOpname $stockOpname): View
    {
        $stockOpname->load('items.inventoryItem.category', 'adjustedBy');

        return view('stock-opname.show', compact('stockOpname'));
    }
}
