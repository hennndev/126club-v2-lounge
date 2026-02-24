<?php

namespace App\Http\Controllers;

use App\Models\BomRecipe;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Printer;
use App\Models\TableSession;
use App\Services\PrinterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(
        protected PrinterService $printerService
    ) {}

    public function index(Request $request)
    {
        // Get BOM recipes with category_type = 'food' or 'bar'
        $bomQuery = BomRecipe::with('inventoryItem')
            ->whereHas('inventoryItem', function ($q) {
                $q->whereIn('category_type', ['food', 'bar']);
            });

        // Get inventory items with category_type = 'drink'
        $inventoryQuery = InventoryItem::where('category_type', 'drink');

        // Search functionality
        if ($request->filled('search')) {
            $bomQuery->whereHas('inventoryItem', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%');
            });
            $inventoryQuery->where('name', 'like', '%'.$request->search.'%');
        }

        // Map BOM recipes to product format
        $bomProducts = $bomQuery->get()->map(function ($bom) {
            return [
                'id' => 'bom_'.$bom->id,
                'bom_id' => $bom->id,
                'name' => $bom->inventoryItem->name ?? 'Unknown',
                'category' => 'drink',
                'price' => $bom->selling_price,
                'stock' => $bom->inventoryItem->quantity ?? 0,
                'type' => 'bom',
            ];
        });

        // Map inventory items to product format
        $inventoryProducts = $inventoryQuery->get()->map(function ($item) {
            return [
                'id' => 'item_'.$item->id,
                'item_id' => $item->id,
                'name' => $item->name,
                'category' => $item->category_type,
                'price' => $item->unit_price ?? 0,
                'stock' => $item->quantity ?? 0,
                'type' => 'item',
            ];
        });

        // Combine both collections and reset keys
        $bomProducts = collect($bomProducts); // walaupun []
        $products = $bomProducts->merge($inventoryProducts)->values();

        // Get cart from session
        $cart = session()->get('pos_cart', []);
        $cartItems = collect($cart)->map(function ($item) {
            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
            ];
        });

        $cartTotal = $cartItems->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });

        // Get active table sessions for booking customers
        $tableSessions = TableSession::with(['customer', 'table.area'])
            ->where('status', 'active')
            ->whereNotNull('checked_in_at')
            ->whereNull('checked_out_at')
            ->get();

        return view('pos.index', compact('products', 'cartItems', 'cartTotal', 'tableSessions'));
    }

    public function addToCart(Request $request, $productId)
    {
        // Check if it's a BOM or inventory item
        if (str_starts_with($productId, 'bom_')) {
            // BOM Recipe
            $bomId = str_replace('bom_', '', $productId);
            $bom = BomRecipe::with('inventoryItem')->find($bomId);

            if (! $bom || ! $bom->inventoryItem || ! in_array($bom->inventoryItem->category_type, ['food', 'bar'])) {
                return back()->with('error', 'Product not found');
            }

            $product = [
                'id' => $productId,
                'name' => $bom->inventoryItem->name,
                'price' => $bom->selling_price,
                'type' => 'bom',
            ];
        } else {
            // Inventory Item
            $itemId = str_replace('item_', '', $productId);
            $inventoryItem = InventoryItem::where('id', $itemId)
                ->where('category_type', 'drink')
                ->first();

            if (! $inventoryItem) {
                return back()->with('error', 'Product not found');
            }

            $product = [
                'id' => $productId,
                'name' => $inventoryItem->name,
                'price' => $inventoryItem->unit_price ?? 0,
                'type' => 'item',
            ];
        }

        $cart = session()->get('pos_cart', []);

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity']++;
        } else {
            $cart[$productId] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => 1,
            ];
        }

        session()->put('pos_cart', $cart);

        return back();
    }

    public function updateCartQuantity(Request $request, $productId)
    {
        $cart = session()->get('pos_cart', []);
        $action = $request->input('action');

        if (isset($cart[$productId])) {
            if ($action === 'increase') {
                $cart[$productId]['quantity']++;
            } elseif ($action === 'decrease') {
                $cart[$productId]['quantity']--;
                if ($cart[$productId]['quantity'] <= 0) {
                    unset($cart[$productId]);
                }
            }
        }

        session()->put('pos_cart', $cart);

        return back();
    }

    public function removeFromCart($productId)
    {
        $cart = session()->get('pos_cart', []);

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
        }

        session()->put('pos_cart', $cart);

        return back();
    }

    public function clearCart()
    {
        session()->forget('pos_cart');

        return back();
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'customer_type' => 'required|in:booking,walk-in',
            'customer_user_id' => 'required|exists:users,id',
            'table_id' => 'nullable|exists:tables,id',
        ]);

        $cart = session()->get('pos_cart', []);

        if (empty($cart)) {
            return back()->with('error', 'Keranjang kosong!');
        }

        \DB::beginTransaction();
        try {
            // Only booking for now
            if ($validated['customer_type'] === 'booking') {
                // Find active table session
                $tableSession = TableSession::where('customer_id', $validated['customer_user_id'])
                    ->where('table_id', $validated['table_id'])
                    ->where('status', 'active')
                    ->first();

                if (! $tableSession) {
                    return back()->with('error', 'Table session tidak ditemukan atau tidak aktif!');
                }

                // Generate order number
                $orderNumber = 'ORD-'.date('Ymd').'-'.str_pad(
                    \App\Models\Order::whereDate('created_at', today())->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

                // Create Order
                $order = \App\Models\Order::create([
                    'table_session_id' => $tableSession->id,
                    'created_by' => auth()->id(),
                    'order_number' => $orderNumber,
                    'status' => 'pending',
                    'items_total' => 0,
                    'discount_amount' => 0,
                    'total' => 0,
                    'ordered_at' => now(),
                ]);

                $itemsTotal = 0;

                // Create Order Items from cart
                foreach ($cart as $productId => $cartItem) {
                    // Determine if BOM or Inventory Item
                    if (str_starts_with($productId, 'bom_')) {
                        $bomId = str_replace('bom_', '', $productId);
                        $bom = BomRecipe::with('inventoryItem')->find($bomId);

                        if (! $bom || ! $bom->inventoryItem) {
                            continue;
                        }

                        $inventoryItemId = $bom->inventory_item_id;
                        $itemName = $bom->inventoryItem->name;
                        $itemCode = $bom->inventoryItem->code;
                        $price = $bom->selling_price;
                        $preparationLocation = in_array($bom->inventoryItem->category_type, ['food']) ? 'kitchen' : 'bar';
                    } else {
                        $itemId = str_replace('item_', '', $productId);
                        $inventoryItem = InventoryItem::find($itemId);

                        if (! $inventoryItem) {
                            continue;
                        }

                        $inventoryItemId = $inventoryItem->id;
                        $itemName = $inventoryItem->name;
                        $itemCode = $inventoryItem->code;
                        $price = $inventoryItem->price;
                        $preparationLocation = 'bar'; // Default untuk drinks
                    }

                    $quantity = $cartItem['quantity'];
                    $subtotal = $price * $quantity;
                    $itemsTotal += $subtotal;

                    // Create Order Item
                    \App\Models\OrderItem::create([
                        'order_id' => $order->id,
                        'inventory_item_id' => $inventoryItemId,
                        'item_name' => $itemName,
                        'item_code' => $itemCode,
                        'quantity' => $quantity,
                        'price' => $price,
                        'subtotal' => $subtotal,
                        'discount_amount' => 0,
                        'preparation_location' => $preparationLocation,
                        'status' => 'pending',
                    ]);
                }

                // Update Order totals
                $order->update([
                    'items_total' => $itemsTotal,
                    'total' => $itemsTotal,
                ]);

                // Update Billing
                if ($tableSession->billing) {
                    $billing = $tableSession->billing;
                    $billing->orders_total = $tableSession->orders()->sum('total');
                    $billing->subtotal = $billing->minimum_charge + $billing->orders_total;
                    $billing->tax = $billing->subtotal * ($billing->tax_percentage / 100);
                    $billing->grand_total = $billing->subtotal + $billing->tax - $billing->discount_amount;
                    $billing->save();
                }

                \DB::commit();

                // Clear cart
                session()->forget('pos_cart');

                // Print receipt if requested
                if ($request->boolean('print_receipt')) {
                    $this->printOrderReceipt($order);
                }

                return redirect()->route('admin.pos.index')
                    ->with('success', "Order #{$orderNumber} berhasil dibuat! Total: Rp ".number_format($itemsTotal, 0, ',', '.'));
            }

            // Walk-in implementation (belakangan)
            return back()->with('error', 'Walk-in belum diimplementasikan');

        } catch (\Exception $e) {
            \DB::rollBack();

            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * Print receipt for a specific order.
     */
    public function printReceipt(Request $request, ?Order $order = null): JsonResponse
    {
        try {
            // If no order provided, try to get from request or session
            if (! $order) {
                $orderId = $request->input('order_id');
                if ($orderId) {
                    $order = Order::with(['items', 'tableSession.table'])->find($orderId);
                }
            } else {
                $order->load(['items', 'tableSession.table']);
            }

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            // Get printer (specific or default)
            $printer = null;
            if ($request->filled('printer_id')) {
                $printer = Printer::active()->find($request->input('printer_id'));
            }
            $printer = $printer ?? Printer::getDefault();

            if (! $printer) {
                return response()->json([
                    'success' => false,
                    'message' => 'No default printer configured.',
                ], 400);
            }

            $this->printerService->printReceipt($order, $printer);

            return response()->json([
                'success' => true,
                'message' => "Receipt for order {$order->order_number} printed successfully.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to print receipt: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test print with dummy data for internal testing.
     */
    public function testPrint(Request $request): JsonResponse
    {
        try {
            $printer = null;
            if ($request->filled('printer_id')) {
                $printer = Printer::active()->find($request->input('printer_id'));
            }
            $printer = $printer ?? Printer::getDefault();

            if (! $printer) {
                return response()->json([
                    'success' => false,
                    'message' => 'No default printer configured.',
                ], 400);
            }

            $this->printerService->testPrint($printer);

            return response()->json([
                'success' => true,
                'message' => 'Test print successful. Check your printer.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test print failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Print receipt for an order (internal method).
     */
    protected function printOrderReceipt(Order $order): bool
    {
        try {
            $printer = Printer::getDefault();

            if (! $printer) {
                return false;
            }

            $order->load(['items', 'tableSession.table']);
            $this->printerService->printReceipt($order, $printer);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
