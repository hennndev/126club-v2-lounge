<?php

use App\Models\Area;
use App\Models\Billing;
use App\Models\GeneralSetting;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Printer;
use App\Models\Tabel;
use App\Models\TableReservation;
use App\Models\TableSession;
use App\Models\User;
use App\Services\PrinterService;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

test('close billing auto prints receipt using configured closed billing printer', function () {
    $admin = adminUser();
    $customer = User::factory()->create();

    $area = Area::create([
        'code' => 'CBP-AREA-'.uniqid(),
        'name' => 'Close Billing Area '.uniqid(),
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'CBP-TBL-'.uniqid(),
        'qr_code' => 'CBP-QR-'.uniqid(),
        'capacity' => 4,
        'status' => 'occupied',
        'is_active' => true,
    ]);

    $booking = TableReservation::create([
        'booking_code' => random_int(1000, 9999),
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'reservation_date' => now()->toDateString(),
        'reservation_time' => '19:00',
        'status' => 'checked_in',
    ]);

    $session = TableSession::create([
        'table_reservation_id' => $booking->id,
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'CBP-SES-'.uniqid(),
        'status' => 'active',
        'checked_in_at' => now(),
    ]);

    $billing = Billing::create([
        'table_session_id' => $session->id,
        'is_walk_in' => false,
        'is_booking' => true,
        'minimum_charge' => 0,
        'orders_total' => 50000,
        'subtotal' => 50000,
        'tax' => 5500,
        'tax_percentage' => 11,
        'service_charge' => 5000,
        'service_charge_percentage' => 10,
        'discount_amount' => 0,
        'grand_total' => 60500,
        'paid_amount' => 0,
        'billing_status' => 'draft',
    ]);

    $session->update(['billing_id' => $billing->id]);

    $order = Order::create([
        'table_session_id' => $session->id,
        'created_by' => $admin->id,
        'order_number' => 'CBP-ORD-'.uniqid(),
        'status' => 'completed',
        'items_total' => 50000,
        'discount_amount' => 0,
        'total' => 50000,
        'ordered_at' => now(),
    ]);

    $inventoryItem = InventoryItem::create([
        'code' => 'CBP-INV-'.uniqid(),
        'accurate_id' => random_int(100000, 999999),
        'name' => 'Close Billing Item '.uniqid(),
        'category_type' => 'beverage',
        'price' => 50000,
        'stock_quantity' => 50,
        'threshold' => 5,
        'unit' => 'glass',
        'is_active' => true,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'inventory_item_id' => $inventoryItem->id,
        'item_name' => 'Menu Close Billing',
        'item_code' => 'CBP-ITEM',
        'quantity' => 1,
        'price' => 50000,
        'subtotal' => 50000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'service_charge_amount' => 0,
        'preparation_location' => 'kitchen',
        'status' => 'served',
    ]);

    $closedBillingPrinter = Printer::create([
        'name' => 'Closed Billing Printer',
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
        'closed_billing_receipt_printer_id' => $closedBillingPrinter->id,
    ]);

    mock(PrinterService::class, function (MockInterface $mock) use ($billing, $session, $closedBillingPrinter): void {
        $mock->shouldReceive('printClosedBillingReceipt')
            ->once()
            ->withArgs(fn ($billingArg, $sessionArg, Printer $printerArg): bool => (int) $billingArg->id === (int) $billing->id
                && (int) $sessionArg->id === (int) $session->id
                && (int) $printerArg->id === (int) $closedBillingPrinter->id)
            ->andReturnTrue();
    });

    actingAs($admin)
        ->postJson(route('admin.bookings.closeBilling', $booking), [
            'payment_mode' => 'normal',
            'payment_method' => 'cash',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('receipt_printed', true)
        ->assertJsonPath('receipt_url', route('admin.bookings.receipt', $booking));
});
