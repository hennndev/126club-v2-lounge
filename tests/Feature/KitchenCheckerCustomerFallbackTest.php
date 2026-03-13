<?php

use App\Models\Area;
use App\Models\InventoryItem;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Order;
use App\Models\Tabel;
use App\Models\TableSession;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('kitchen fetch uses booking session customer when kitchen order customer_user_id is null', function () {
    $admin = adminUser();
    $customer = User::factory()->create(['name' => 'Booking Customer']);

    $area = Area::create([
        'code' => 'KIT-'.uniqid(),
        'name' => 'Kitchen Area',
        'capacity' => 10,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'K-01',
        'qr_code' => 'QR-K-01-'.uniqid(),
        'capacity' => 4,
        'status' => 'occupied',
        'is_active' => true,
    ]);

    $session = TableSession::create([
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SESS-KIT-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    $order = Order::create([
        'table_session_id' => $session->id,
        'created_by' => $admin->id,
        'order_number' => 'ORD-KIT-'.uniqid(),
        'status' => 'pending',
        'items_total' => 25000,
        'discount_amount' => 0,
        'total' => 25000,
        'ordered_at' => now(),
    ]);

    $kitchenOrder = KitchenOrder::create([
        'order_id' => $order->id,
        'order_number' => $order->order_number,
        'customer_user_id' => null,
        'table_id' => $table->id,
        'total_amount' => 25000,
        'status' => 'baru',
        'progress' => 0,
    ]);

    $item = InventoryItem::create([
        'code' => 'KIT-ITEM-001',
        'accurate_id' => 99001,
        'name' => 'Ayam Bakar',
        'category_type' => 'food',
        'price' => 25000,
        'stock_quantity' => 20,
        'threshold' => 5,
        'is_active' => true,
    ]);

    KitchenOrderItem::create([
        'kitchen_order_id' => $kitchenOrder->id,
        'inventory_item_id' => $item->id,
        'quantity' => 1,
        'price' => 25000,
        'is_completed' => false,
    ]);

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->getJson(route('admin.kitchen.fetch'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('orders.0.customer.name', 'Booking Customer')
        ->assertJsonPath('orders.0.items.0.item_name', 'Ayam Bakar');
});
