<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\BarOrder;
use App\Models\BarOrderItem;
use App\Models\BomRecipe;
use App\Models\InventoryItem;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Printer;
use App\Models\TableSession;
use App\Models\Tier;
use App\Services\PrinterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // Get inventory items with category_type = 'beverage'
        $inventoryQuery = InventoryItem::where('category_type', 'beverage');

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
                'category' => $bom->inventoryItem->category_type ?? 'food',
                'price' => $bom->selling_price,
                'type' => 'bom',
                'is_available' => $bom->is_available,
            ];
        });

        // Map inventory items to product format
        $inventoryProducts = $inventoryQuery->get()->map(function ($item) {
            return [
                'id' => 'item_'.$item->id,
                'item_id' => $item->id,
                'name' => $item->name,
                'category' => $item->category_type,
                'price' => $item->price ?? 0,
                'stock' => $item->stock_quantity ?? 0,
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
                'preparation_location' => $item['preparation_location'] ?? 'bar',
            ];
        });

        $cartTotal = $cartItems->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });

        // Get active table sessions for booking customers
        $tableSessions = TableSession::with(['customer.profile', 'customer.customerUser', 'table.area', 'billing'])
            ->where('status', 'active')
            ->whereNotNull('checked_in_at')
            ->whereNull('checked_out_at')
            ->get();

        // Get waiters for the Pilih Waiter dropdown
        $waiters = \App\Models\User::role('Waiter/Server')
            ->with('profile')
            ->get();

        // Get tiers for discount calculation (highest level first)
        $tiers = Tier::orderBy('level', 'desc')->get();

        // Get printer locations for counter selection
        $printerLocations = $this->getPrinterLocations();

        // Get current counter location from session
        $currentCounter = session()->get('pos_counter_location');

        return view('pos.index', compact('products', 'cartItems', 'cartTotal', 'tableSessions', 'waiters', 'tiers', 'printerLocations', 'currentCounter'));
    }

    /**
     * Get valid printer locations (service + area locations).
     */
    protected function getPrinterLocations(): array
    {
        $serviceLocations = [
            'kitchen' => 'Kitchen',
            'bar' => 'Bar',
            'cashier' => 'Cashier',
        ];

        $areaLocations = Area::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'code')
            ->toArray();

        return [
            'Service' => $serviceLocations,
            'Areas' => $areaLocations,
        ];
    }

    /**
     * Select counter location for current session.
     */
    public function selectCounter(Request $request): JsonResponse
    {
        $request->validate([
            'counter_location' => 'required|string',
        ]);

        session()->put('pos_counter_location', $request->counter_location);

        return response()->json([
            'success' => true,
            'message' => 'Counter location set successfully.',
            'counter_location' => $request->counter_location,
        ]);
    }

    public function addToCart(Request $request, $productId): JsonResponse
    {
        // Check if it's a BOM or inventory item
        if (str_starts_with($productId, 'bom_')) {
            // BOM Recipe
            $bomId = str_replace('bom_', '', $productId);
            $bom = BomRecipe::with('inventoryItem')->find($bomId);

            if (! $bom || ! $bom->inventoryItem || ! in_array($bom->inventoryItem->category_type, ['food', 'bar'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            $product = [
                'id' => $productId,
                'name' => $bom->inventoryItem->name,
                'price' => $bom->selling_price,
                'type' => 'bom',
                'preparation_location' => in_array($bom->inventoryItem->category_type, ['food']) ? 'kitchen' : 'bar',
            ];
        } else {
            // Inventory Item
            $itemId = str_replace('item_', '', $productId);
            $inventoryItem = InventoryItem::where('id', $itemId)
                ->where('category_type', 'beverage')
                ->first();

            if (! $inventoryItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            $product = [
                'id' => $productId,
                'name' => $inventoryItem->name,
                'price' => $inventoryItem->price ?? 0,
                'type' => 'item',
                'preparation_location' => 'bar',
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
                'preparation_location' => $product['preparation_location'],
            ];
        }

        session()->put('pos_cart', $cart);

        return $this->cartResponse('Product added to cart', $cart);
    }

    public function updateCartQuantity(Request $request, $productId): JsonResponse
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

        return $this->cartResponse('Cart updated', $cart);
    }

    public function removeFromCart($productId): JsonResponse
    {
        $cart = session()->get('pos_cart', []);

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
        }

        session()->put('pos_cart', $cart);

        return $this->cartResponse('Item removed from cart', $cart);
    }

    public function clearCart(): JsonResponse
    {
        session()->forget('pos_cart');

        return $this->cartResponse('Cart cleared', []);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_type' => 'required|in:booking,walk-in',
            'customer_user_id' => 'required|exists:users,id',
            'table_id' => 'nullable|exists:tables,id',
            'waiter_id' => 'nullable|exists:users,id',
            'payment_method' => 'nullable|in:cash,kredit,debit',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
        ]);

        $cart = session()->get('pos_cart', []);

        if (empty($cart)) {
            return response()->json([
                'success' => false,
                'message' => 'Keranjang kosong!',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Only booking for now
            if ($validated['customer_type'] === 'booking') {
                // Find active table session
                $tableSession = TableSession::where('customer_id', $validated['customer_user_id'])
                    ->where('table_id', $validated['table_id'])
                    ->where('status', 'active')
                    ->first();

                if (! $tableSession) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Table session tidak ditemukan atau tidak aktif!',
                    ], 404);
                }

                // Generate order number
                $orderNumber = 'ORD-'.date('Ymd').'-'.str_pad(
                    Order::whereDate('created_at', today())->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

                $discountPercentage = (int) ($validated['discount_percentage'] ?? 0);
                $paymentNotes = isset($validated['payment_method']) ? 'Payment: '.strtoupper($validated['payment_method']) : null;

                // Create Order
                $order = Order::create([
                    'table_session_id' => $tableSession->id,
                    'created_by' => auth()->id(),
                    'order_number' => $orderNumber,
                    'status' => 'pending',
                    'items_total' => 0,
                    'discount_amount' => 0,
                    'total' => 0,
                    'ordered_at' => now(),
                    'notes' => $paymentNotes,
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
                    OrderItem::create([
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

                // Apply tier discount
                $discountAmount = (int) round($itemsTotal * $discountPercentage / 100);
                $finalTotal = $itemsTotal - $discountAmount;

                // Update Order totals
                $order->update([
                    'items_total' => $itemsTotal,
                    'discount_amount' => $discountAmount,
                    'total' => $finalTotal,
                ]);

                // Route items to Kitchen/Bar and print tickets
                $this->routeOrderToPreparation($order, $tableSession, $orderNumber);

                // Update Billing
                if ($tableSession->billing) {
                    $billing = $tableSession->billing;
                    $billing->orders_total = $tableSession->orders()->sum('total');
                    $billing->subtotal = $billing->minimum_charge + $billing->orders_total;
                    $billing->tax = $billing->subtotal * ($billing->tax_percentage / 100);
                    $billing->grand_total = $billing->subtotal + $billing->tax - $billing->discount_amount;
                    $billing->save();
                }

                DB::commit();

                // Clear cart
                session()->forget('pos_cart');

                // Print receipt if requested
                if ($request->boolean('print_receipt')) {
                    $this->printOrderReceipt($order);
                }

                return response()->json([
                    'success' => true,
                    'message' => "Order #{$orderNumber} berhasil dibuat!",
                    'order_number' => $orderNumber,
                    'order_id' => $order->id,
                    'total' => $finalTotal,
                    'formatted_total' => 'Rp '.number_format($finalTotal, 0, ',', '.'),
                ]);
            }

            // Walk-in implementation (belakangan)
            return response()->json([
                'success' => false,
                'message' => 'Walk-in belum diimplementasikan',
            ], 400);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 500);
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

    /**
     * Route order items to Kitchen/Bar preparation queues and print tickets.
     */
    protected function routeOrderToPreparation(Order $order, TableSession $tableSession, string $orderNumber): void
    {
        $kitchenItems = collect();
        $barItems = collect();

        // Categorize items by preparation location
        foreach ($order->items as $item) {
            if ($item->preparation_location === 'kitchen') {
                $kitchenItems->push($item);
            } else {
                $barItems->push($item);
            }
        }

        // Get customer_users.id from users.id
        $customerUser = \App\Models\CustomerUser::where('user_id', $tableSession->customer_id)->first();
        $customerUserId = $customerUser?->id;

        // Create Kitchen Order if there are kitchen items
        if ($kitchenItems->isNotEmpty()) {
            $kitchenOrder = KitchenOrder::create([
                'order_id' => $order->id,
                'order_number' => $orderNumber,
                'customer_user_id' => $customerUserId,
                'table_id' => $tableSession->table_id,
                'total_amount' => $kitchenItems->sum('subtotal'),
                'status' => 'baru',
                'progress' => 0,
            ]);

            // Create kitchen order items
            foreach ($kitchenItems as $item) {
                // Find the BOM recipe for this item
                $bomRecipe = BomRecipe::where('inventory_item_id', $item->inventory_item_id)->first();

                if ($bomRecipe) {
                    KitchenOrderItem::create([
                        'kitchen_order_id' => $kitchenOrder->id,
                        'bom_recipe_id' => $bomRecipe->id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'is_completed' => false,
                    ]);
                }
            }

            // Auto-print kitchen ticket if a printer is configured for 'kitchen' location
            $this->printKitchenTicket($kitchenOrder);
        }

        // Create Bar Order if there are bar items
        if ($barItems->isNotEmpty()) {
            $barOrder = BarOrder::create([
                'order_id' => $order->id,
                'order_number' => $orderNumber,
                'customer_user_id' => $customerUserId,
                'table_id' => $tableSession->table_id,
                'total_amount' => $barItems->sum('subtotal'),
                'status' => 'baru',
                'progress' => 0,
            ]);

            // Create bar order items
            foreach ($barItems as $item) {
                $bomRecipe = BomRecipe::where('inventory_item_id', $item->inventory_item_id)->first();

                BarOrderItem::create([
                    'bar_order_id' => $barOrder->id,
                    'bom_recipe_id' => $bomRecipe?->id,
                    'inventory_item_id' => $bomRecipe ? null : $item->inventory_item_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'is_completed' => false,
                ]);
            }

            // Auto-print bar ticket if a printer is configured for 'bar' location
            $this->printBarTicket($barOrder);
        }
    }

    /**
     * Print kitchen order ticket.
     */
    protected function printKitchenTicket(KitchenOrder $kitchenOrder): bool
    {
        try {
            $printer = $this->getPrinterForLocation('kitchen');

            if (! $printer) {
                return false;
            }

            $kitchenOrder->load(['items.recipe.inventoryItem', 'table']);
            $this->printerService->printKitchenTicket($kitchenOrder, $printer);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Print bar order ticket.
     */
    protected function printBarTicket(BarOrder $barOrder): bool
    {
        try {
            $printer = $this->getPrinterForLocation('bar');

            if (! $printer) {
                return false;
            }

            $barOrder->load(['items.recipe.inventoryItem', 'items.inventoryItem', 'table']);
            $this->printerService->printBarTicket($barOrder, $printer);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get printer for a service location, considering counter location from session.
     * Priority: counter location > service location > default.
     */
    protected function getPrinterForLocation(string $serviceLocation): ?Printer
    {
        $counterLocation = session()->get('pos_counter_location');

        // 1. Try counter location first (area-specific printer)
        if ($counterLocation) {
            $printer = Printer::getByLocation($counterLocation);
            if ($printer) {
                return $printer;
            }
        }

        // 2. Fallback to service location printer (kitchen/bar)
        $printer = Printer::getByLocation($serviceLocation);
        if ($printer) {
            return $printer;
        }

        // 3. Final fallback to default printer
        return Printer::getDefault();
    }

    /**
     * Build a standardized cart response for AJAX requests.
     */
    protected function cartResponse(string $message, array $cart): JsonResponse
    {
        $cartItems = collect($cart)->values()->map(function ($item) {
            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => (float) $item['price'],
                'quantity' => (int) $item['quantity'],
                'subtotal' => (float) $item['price'] * (int) $item['quantity'],
                'preparation_location' => $item['preparation_location'] ?? 'bar',
            ];
        });

        $cartTotal = $cartItems->sum('subtotal');

        return response()->json([
            'success' => true,
            'message' => $message,
            'cart' => $cartItems,
            'cartTotal' => $cartTotal,
            'itemCount' => $cartItems->count(),
        ]);
    }
}
