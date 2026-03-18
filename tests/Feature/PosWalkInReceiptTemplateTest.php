<?php

use App\Models\Billing;
use App\Models\CustomerUser;
use App\Models\GeneralSetting;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Printer;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\PrinterService;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

test('walk in receipt printing uses walk in billing template formatter', function () {
    $admin = adminUser();

    $customer = User::factory()->create();
    $profile = UserProfile::create([
        'user_id' => $customer->id,
        'phone' => '081299998888',
    ]);

    $customerUser = CustomerUser::create([
        'user_id' => $customer->id,
        'user_profile_id' => $profile->id,
        'accurate_id' => null,
        'customer_code' => null,
        'total_visits' => 0,
        'lifetime_spending' => 0,
    ]);

    $order = Order::create([
        'table_session_id' => null,
        'customer_user_id' => $customerUser->id,
        'created_by' => $admin->id,
        'order_number' => 'WALK-PRINT-'.uniqid(),
        'status' => 'completed',
        'items_total' => 50000,
        'discount_amount' => 0,
        'total' => 61050,
        'ordered_at' => now(),
        'payment_method' => 'cash',
        'payment_mode' => 'normal',
    ]);

    $inventoryItem = InventoryItem::create([
        'code' => 'WALK-RCP-'.uniqid(),
        'accurate_id' => random_int(100000, 999999),
        'name' => 'Walk In Inventory '.uniqid(),
        'category_type' => 'beverage',
        'price' => 50000,
        'stock_quantity' => 100,
        'threshold' => 5,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'inventory_item_id' => $inventoryItem->id,
        'item_name' => 'Walk In Receipt Item',
        'item_code' => 'WALK-ITEM-1',
        'quantity' => 1,
        'price' => 50000,
        'subtotal' => 50000,
        'discount_amount' => 0,
        'tax_amount' => 6050,
        'service_charge_amount' => 5000,
        'preparation_location' => 'kitchen',
        'status' => 'served',
    ]);

    $billing = Billing::create([
        'table_session_id' => null,
        'order_id' => $order->id,
        'is_walk_in' => true,
        'is_booking' => false,
        'minimum_charge' => 0,
        'orders_total' => 50000,
        'subtotal' => 50000,
        'tax' => 6050,
        'tax_percentage' => 11,
        'service_charge' => 5000,
        'service_charge_percentage' => 10,
        'discount_amount' => 0,
        'grand_total' => 61050,
        'paid_amount' => 61050,
        'billing_status' => 'paid',
        'transaction_code' => 'TRX-WALK-'.uniqid(),
        'payment_method' => 'cash',
        'payment_mode' => 'normal',
    ]);

    $walkInPrinter = Printer::create([
        'name' => 'Walk In Printer',
        'location' => 'cashier',
        'printer_type' => 'cashier',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => false,
        'is_active' => true,
    ]);

    GeneralSetting::instance()->update([
        'walk_in_receipt_printer_id' => $walkInPrinter->id,
    ]);

    mock(PrinterService::class, function (MockInterface $mock) use ($order, $billing, $walkInPrinter): void {
        $mock->shouldReceive('printWalkInBillingReceipt')
            ->once()
            ->withArgs(fn ($orderArg, $billingArg, Printer $printerArg): bool => (int) $orderArg->id === (int) $order->id
                && (int) $billingArg->id === (int) $billing->id
                && (int) $printerArg->id === (int) $walkInPrinter->id)
            ->andReturnTrue();

        $mock->shouldReceive('printReceipt')->never();
    });

    actingAs($admin)
        ->postJson(route('admin.pos.print-receipt', $order))
        ->assertOk()
        ->assertJsonPath('success', true);
});
