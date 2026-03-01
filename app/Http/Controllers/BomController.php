<?php

namespace App\Http\Controllers;

use App\Models\BomRecipe;
use App\Models\BomRecipeItem;
use App\Models\InventoryItem;
use App\Services\AccurateService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BomController extends Controller
{
    protected $accurateService;

    public function __construct(AccurateService $accurateService)
    {
        $this->accurateService = $accurateService;
    }

    public function index(Request $request)
    {
        $query = BomRecipe::with('items.inventoryItem');

        // Filter by type
        if ($request->has('type') && in_array($request->type, ['food', 'beverage'])) {
            $query->where('type', $request->type);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $recipes = $query->latest()->get();

        // Calculate stats
        $totalRecipes = BomRecipe::count();
        $foodRecipes = BomRecipe::where('type', 'food')->count();
        $beverageRecipes = BomRecipe::where('type', 'beverage')->count();

        $inventoryItems = InventoryItem::where('is_active', true)
            ->get();

        return view('bom.index', compact('recipes', 'totalRecipes', 'foodRecipes', 'beverageRecipes', 'inventoryItems'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|integer|min:1',
            'type' => 'required|in:food,beverage',
            'description' => 'nullable|string',
            'selling_price' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();

        try {
            $accurateId = null;

            // Prepare data for Accurate API
            $detailMaterial = collect($request->items)->map(function ($material) {
                $item = InventoryItem::find($material['inventory_item_id']);

                return [
                    'itemNo' => $item?->code,
                    'detailName' => $item->name,
                    'itemUnitName' => $item->unit,
                    'quantity' => $material['quantity'],
                    'standardCost' => $item->price,
                    'totalStandardCost' => $material['quantity'] * $item->price,
                ];
            })->values()->toArray();

            $item = InventoryItem::find($validated['inventory_item_id']);
            $payload = [
                'itemNo' => $item->code,
                'quantity' => $request->quantity,
                'description' => $validated['description'] ?? 'BOM Creation',
                'detailMaterial' => $detailMaterial,
            ];

            // Save to Accurate
            $response = $this->accurateService->saveBOM($payload);
            $accurateId = $response['r']['id'] ?? null;

            if (! $accurateId) {
                throw new Exception('Failed to get Accurate ID from API response');
            }

            // Create BOM Recipe
            $recipe = BomRecipe::create([
                'accurate_id' => $accurateId,
                'inventory_item_id' => $validated['inventory_item_id'],
                'quantity' => $validated['quantity'],
                'type' => $validated['type'],
                'description' => $validated['description'],
                'selling_price' => $validated['selling_price'],
                'total_cost' => 0,
                'is_active' => true,
            ]);

            // Create BOM Recipe Items
            $totalCost = 0;
            foreach ($validated['items'] as $item) {
                $inventoryItem = InventoryItem::find($item['inventory_item_id']);
                $cost = $inventoryItem->price * $item['quantity'];

                BomRecipeItem::create([
                    'bom_recipe_id' => $recipe->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $inventoryItem->unit,
                    'cost' => $cost,
                ]);

                $totalCost += $cost;
            }

            // Update total cost
            $recipe->update(['total_cost' => $totalCost]);

            DB::commit();

            Log::info('BOM Recipe created successfully', [
                'recipe_id' => $recipe->id,
                'accurate_id' => $accurateId,
                'total_cost' => $totalCost,
            ]);

            return redirect()->route('admin.bom.index')
                ->with('success', 'Recipe berhasil ditambahkan!');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to create BOM Recipe', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $validated,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan recipe: '.$e->getMessage());
        }
    }

    public function update(Request $request, BomRecipe $bom)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|integer|min:1',
            'type' => 'required|in:food,beverage',
            'description' => 'nullable|string',
            'selling_price' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();

        try {
            // Update BOM Recipe
            $bom->update([
                'inventory_item_id' => $validated['inventory_item_id'],
                'quantity' => $validated['quantity'],
                'type' => $validated['type'],
                'description' => $validated['description'],
                'selling_price' => $validated['selling_price'],
            ]);

            // Delete old items
            $bom->items()->delete();

            // Add new items
            $totalCost = 0;
            foreach ($validated['items'] as $item) {
                $inventoryItem = InventoryItem::find($item['inventory_item_id']);

                if (! $inventoryItem) {
                    throw new Exception("Inventory item not found: {$item['inventory_item_id']}");
                }

                $cost = $inventoryItem->price * $item['quantity'];

                BomRecipeItem::create([
                    'bom_recipe_id' => $bom->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $inventoryItem->unit,
                    'cost' => $cost,
                ]);

                $totalCost += $cost;
            }

            // Update total cost
            $bom->update(['total_cost' => $totalCost]);

            DB::commit();

            Log::info('BOM Recipe updated successfully', [
                'recipe_id' => $bom->id,
                'total_cost' => $totalCost,
            ]);

            return redirect()->route('admin.bom.index')
                ->with('success', 'Recipe berhasil diupdate!');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to update BOM Recipe', [
                'recipe_id' => $bom->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $validated,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal mengupdate recipe: '.$e->getMessage());
        }
    }

    public function destroy(BomRecipe $bom)
    {
        $bom->delete();

        return redirect()->route('admin.bom.index')->with('success', 'Recipe berhasil dihapus!');
    }

    public function toggleStatus(BomRecipe $bom)
    {
        $bom->update(['is_active' => ! $bom->is_active]);

        return redirect()->route('admin.bom.index')->with('success', 'Status recipe berhasil diupdate!');
    }

    public function toggleAvailability(BomRecipe $bom)
    {
        $bom->update(['is_available' => ! $bom->is_available]);
        $label = $bom->is_available ? 'Tersedia' : 'Habis';

        return redirect()->route('admin.bom.index')->with('success', "Ketersediaan recipe diubah menjadi: {$label}");
    }
}
