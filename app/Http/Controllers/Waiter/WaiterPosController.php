<?php

namespace App\Http\Controllers\Waiter;

use App\Http\Controllers\Controller;
use App\Models\BarOrder;
use App\Models\BarOrderItem;
use App\Models\CustomerUser;
use App\Models\InventoryItem;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PosCategorySetting;
use App\Models\Printer;
use App\Models\TableSession;
use App\Services\AccurateService;
use App\Services\PrinterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WaiterPosController extends Controller
{
    public const CART_KEY = 'waiter_pos_cart';

    public const SESSION_KEY = 'waiter_pos_selected_session';

    public function __construct(
        protected PrinterService $printerService,
        protected AccurateService $accurateService,
    ) {}

    public function addToCart(Request $request, string $productId): JsonResponse
    {
        $posSettings = PosCategorySetting::allKeyed();

        $itemId = str_replace('item_', '', $productId);
        $inventoryItem = InventoryItem::find($itemId);
        $setting = $posSettings->get($inventoryItem?->category_type);

        if (! $inventoryItem || ! $setting || ! $setting->show_in_pos) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
        }

        $cart = session()->get(self::CART_KEY, []);
        $nextQty = (int) ($cart[$productId]['quantity'] ?? 0) + 1;

        if (! $setting->is_menu) {
            if ((int) ($inventoryItem->stock_quantity ?? 0) < $nextQty) {
                return response()->json(['success' => false, 'message' => 'Stok tidak mencukupi.'], 422);
            }
        } else {
            $possiblePortions = $this->resolvePossiblePortions($inventoryItem);

            if ($possiblePortions < $nextQty) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok bahan {$inventoryItem->name} hanya cukup {$possiblePortions} porsi.",
                    'possible_portions' => $possiblePortions,
                ], 422);
            }
        }

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity']++;
        } else {
            $cart[$productId] = [
                'id' => $productId,
                'name' => $inventoryItem->name,
                'price' => (float) $inventoryItem->price,
                'quantity' => 1,
                'preparation_location' => $setting->preparation_location ?? 'direct',
            ];
        }

        session()->put(self::CART_KEY, $cart);

        return $this->cartResponse($cart);
    }

    public function updateCart(Request $request, string $productId): JsonResponse
    {
        $validated = $request->validate(['quantity' => 'required|integer|min:0']);

        $cart = session()->get(self::CART_KEY, []);

        if ($validated['quantity'] <= 0) {
            unset($cart[$productId]);
        } elseif (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = $validated['quantity'];
        }

        session()->put(self::CART_KEY, $cart);

        return $this->cartResponse($cart);
    }

    public function removeFromCart(string $productId): JsonResponse
    {
        $cart = session()->get(self::CART_KEY, []);
        unset($cart[$productId]);
        session()->put(self::CART_KEY, $cart);

        return $this->cartResponse($cart);
    }

    public function selectSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|integer|exists:table_sessions,id',
        ]);

        $session = TableSession::query()
            ->whereKey($validated['session_id'])
            ->where('waiter_id', (int) Auth::id())
            ->whereNotNull('table_reservation_id')
            ->where('status', 'active')
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya meja booking aktif yang di-assign ke Anda yang bisa dipilih.',
            ], 422);
        }

        session()->put(self::SESSION_KEY, $session->id);

        return response()->json(['success' => true]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:table_sessions,id',
        ]);

        $waiterId = (int) Auth::id();

        $tableSession = TableSession::with(['table', 'billing', 'orders'])
            ->where('id', $validated['session_id'])
            ->where('waiter_id', $waiterId)
            ->whereNotNull('table_reservation_id')
            ->where('status', 'active')
            ->first();

        if (! $tableSession) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya meja booking aktif yang di-assign ke Anda yang bisa checkout.',
            ], 422);
        }

        $cart = session()->get(self::CART_KEY, []);

        if (empty($cart)) {
            return response()->json(['success' => false, 'message' => 'Keranjang kosong.'], 400);
        }

        $availability = $this->resolveCartAvailability($cart);

        if (! $availability['can_checkout']) {
            return response()->json([
                'success' => false,
                'message' => $availability['message'],
                'stock_issues' => $availability['stock_issues'],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $orderNumber = 'ORD-'.date('Ymd').'-'.str_pad(
                Order::whereDate('created_at', today())->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            $order = Order::create([
                'table_session_id' => $tableSession->id,
                'created_by' => Auth::id(),
                'order_number' => $orderNumber,
                'status' => 'pending',
                'items_total' => 0,
                'discount_amount' => 0,
                'total' => 0,
                'ordered_at' => now(),
                'notes' => null,
            ]);

            $posSettings = PosCategorySetting::allKeyed();
            $itemsTotal = 0;

            foreach ($cart as $productId => $cartItem) {
                $itemId = str_replace('item_', '', $productId);
                $inventoryItem = InventoryItem::find($itemId);

                if (! $inventoryItem) {
                    continue;
                }

                $setting = $posSettings->get($inventoryItem->category_type);
                $preparationLocation = $setting?->preparation_location ?? $cartItem['preparation_location'] ?? 'direct';

                $price = (float) $inventoryItem->price;
                $quantity = (int) $cartItem['quantity'];
                $subtotal = $price * $quantity;
                $itemsTotal += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'inventory_item_id' => $inventoryItem->id,
                    'item_name' => $inventoryItem->name,
                    'item_code' => $inventoryItem->code,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                    'discount_amount' => 0,
                    'preparation_location' => $preparationLocation,
                    'status' => 'pending',
                    'notes' => null,
                ]);
            }

            $order->update([
                'items_total' => $itemsTotal,
                'total' => $itemsTotal,
            ]);

            $order->load('items');
            $this->routeOrderToPreparation($order, $tableSession, $orderNumber);

            if ($tableSession->billing) {
                $billing = $tableSession->billing;
                $billing->orders_total = $tableSession->orders()->sum('total');
                $billing->subtotal = max((float) $billing->minimum_charge, (float) $billing->orders_total);
                $billing->tax = 0;
                $billing->grand_total = $billing->subtotal - $billing->discount_amount;
                $billing->save();
            }

            session()->forget(self::CART_KEY);
            session()->forget(self::SESSION_KEY);

            DB::commit();

            return response()->json(['success' => true, 'order_number' => $orderNumber]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: '.$e->getMessage()], 500);
        }
    }

    protected function routeOrderToPreparation(Order $order, TableSession $tableSession, string $orderNumber): void
    {
        $kitchenItems = collect();
        $barItems = collect();

        foreach ($order->items as $item) {
            if ($item->preparation_location === 'kitchen') {
                $kitchenItems->push($item);
            } elseif ($item->preparation_location === 'bar') {
                $barItems->push($item);
            }
        }

        $customerUserId = CustomerUser::where('user_id', $tableSession->customer_id)
            ->value('id');

        $tableId = $tableSession->table_id;

        if ($kitchenItems->isNotEmpty()) {
            $kitchenOrder = KitchenOrder::create([
                'order_id' => $order->id,
                'order_number' => $orderNumber,
                'customer_user_id' => $customerUserId,
                'table_id' => $tableId,
                'total_amount' => $kitchenItems->sum('subtotal'),
                'status' => 'baru',
                'progress' => 0,
            ]);

            foreach ($kitchenItems as $item) {
                KitchenOrderItem::create([
                    'kitchen_order_id' => $kitchenOrder->id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'is_completed' => false,
                    'notes' => $item->notes,
                ]);
            }

            $this->printKitchenTicket($kitchenOrder);
        }

        if ($barItems->isNotEmpty()) {
            $barOrder = BarOrder::create([
                'order_id' => $order->id,
                'order_number' => $orderNumber,
                'customer_user_id' => $customerUserId,
                'table_id' => $tableId,
                'total_amount' => $barItems->sum('subtotal'),
                'status' => 'baru',
                'progress' => 0,
            ]);

            foreach ($barItems as $item) {
                BarOrderItem::create([
                    'bar_order_id' => $barOrder->id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'is_completed' => false,
                    'notes' => $item->notes,
                ]);
            }

            $this->printBarTicket($barOrder);
        }
    }

    protected function printKitchenTicket(KitchenOrder $kitchenOrder): void
    {
        try {
            $printer = Printer::byLocation('kitchen')->first();

            if ($printer) {
                $kitchenOrder->load(['items.inventoryItem', 'table']);
                $this->printerService->printKitchenTicket($kitchenOrder, $printer);
            }
        } catch (\Exception $e) {
            // Silent fail — don't block checkout
        }
    }

    protected function printBarTicket(BarOrder $barOrder): void
    {
        try {
            $printer = Printer::byLocation('bar')->first();

            if ($printer) {
                $barOrder->load(['items.inventoryItem', 'table']);
                $this->printerService->printBarTicket($barOrder, $printer);
            }
        } catch (\Exception $e) {
            // Silent fail — don't block checkout
        }
    }

    protected function cartResponse(array $cart): JsonResponse
    {
        $formatted = collect($cart)->mapWithKeys(fn ($item, $key) => [
            $key => [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => (float) $item['price'],
                'qty' => (int) $item['quantity'],
            ],
        ])->all();

        return response()->json(['success' => true, 'cart' => $formatted]);
    }

    protected function resolveCartAvailability(array $cart): array
    {
        $posSettings = PosCategorySetting::allKeyed();
        $stockIssues = [];

        foreach ($cart as $productId => $cartItem) {
            $itemId = (int) str_replace('item_', '', (string) $productId);
            $inventoryItem = InventoryItem::find($itemId);
            $requestedQuantity = (int) ($cartItem['quantity'] ?? 0);

            if (! $inventoryItem || $requestedQuantity <= 0) {
                continue;
            }

            $setting = $posSettings->get($inventoryItem->category_type);

            if (! $setting?->is_menu) {
                $availableStock = (float) ($inventoryItem->stock_quantity ?? 0);

                if ($availableStock < $requestedQuantity) {
                    $stockIssues[] = [
                        'type' => 'stock',
                        'product_id' => $productId,
                        'name' => $inventoryItem->name,
                        'available_stock' => $availableStock,
                        'requested_quantity' => $requestedQuantity,
                        'message' => "Stok {$inventoryItem->name} hanya tersisa {$availableStock}.",
                    ];
                }

                continue;
            }

            $possiblePortions = $this->resolvePossiblePortions($inventoryItem);

            if ($possiblePortions < $requestedQuantity) {
                $stockIssues[] = [
                    'type' => 'menu_portion',
                    'product_id' => $productId,
                    'name' => $inventoryItem->name,
                    'requested_quantity' => $requestedQuantity,
                    'possible_portions' => $possiblePortions,
                    'message' => "Stok bahan {$inventoryItem->name} hanya cukup {$possiblePortions} porsi.",
                ];
            }
        }

        return [
            'can_checkout' => $stockIssues === [],
            'message' => $stockIssues[0]['message'] ?? 'Stok menu siap untuk checkout.',
            'stock_issues' => $stockIssues,
        ];
    }

    protected function resolvePossiblePortions(InventoryItem $inventoryItem): int
    {
        if (! $inventoryItem->accurate_id) {
            return 0;
        }

        try {
            $components = $this->accurateService->getItemGroupComponents((int) $inventoryItem->accurate_id);
        } catch (\Throwable $exception) {
            return 0;
        }

        if ($components === []) {
            return 0;
        }

        $linePossiblePortions = null;

        foreach ($components as $component) {
            $componentAccurateId = (int) ($component['itemId'] ?? 0);
            $componentQuantity = (float) ($component['quantity'] ?? 0);

            if ($componentAccurateId <= 0 || $componentQuantity <= 0) {
                continue;
            }

            $ingredient = InventoryItem::query()
                ->where('accurate_id', $componentAccurateId)
                ->first();

            $availableStock = max((float) ($ingredient?->stock_quantity ?? 0), 0);
            $possibleByIngredient = (int) floor($availableStock / $componentQuantity);

            $linePossiblePortions = $linePossiblePortions === null
                ? $possibleByIngredient
                : min($linePossiblePortions, $possibleByIngredient);
        }

        return $linePossiblePortions ?? 0;
    }
}
