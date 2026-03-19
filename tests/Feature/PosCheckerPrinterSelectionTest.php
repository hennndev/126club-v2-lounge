<?php

use App\Models\Area;
use App\Models\GeneralSetting;
use App\Models\InventoryItem;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\Order;
use App\Models\Printer;
use App\Models\Tabel;
use App\Services\PrinterService;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

function createCheckerSelectionFixture(): array
{
    $admin = adminUser();

    $area = Area::create([
        'code' => 'AR-'.uniqid(),
        'name' => 'Area '.uniqid(),
        'capacity' => 10,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'T-01',
        'qr_code' => 'QR-T-'.uniqid(),
        'capacity' => 4,
        'status' => 'occupied',
        'is_active' => true,
    ]);

    $order = Order::create([
        'table_session_id' => null,
        'customer_user_id' => null,
        'created_by' => $admin->id,
        'order_number' => 'ORD-CHK-'.uniqid(),
        'status' => 'pending',
        'items_total' => 25000,
        'discount_amount' => 0,
        'total' => 25000,
        'ordered_at' => now(),
    ]);

    $inventoryItem = InventoryItem::create([
        'code' => 'ITEM-'.uniqid(),
        'accurate_id' => random_int(100000, 999999),
        'name' => 'Checker Selection Item '.uniqid(),
        'category_type' => 'food',
        'price' => 25000,
        'stock_quantity' => 100,
        'threshold' => 5,
        'unit' => 'pcs',
        'is_active' => true,
    ]);

    $checkerPrinterOne = Printer::create([
        'name' => 'Checker A',
        'location' => 'checker',
        'printer_type' => 'checker',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => false,
        'is_active' => true,
    ]);

    $checkerPrinterTwo = Printer::create([
        'name' => 'Checker B',
        'location' => 'checker',
        'printer_type' => 'checker',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => false,
        'is_active' => true,
    ]);

    $inventoryItem->printers()->attach([$checkerPrinterOne->id, $checkerPrinterTwo->id]);

    $kitchenOrder = KitchenOrder::create([
        'order_id' => $order->id,
        'order_number' => $order->order_number,
        'customer_user_id' => null,
        'table_id' => $table->id,
        'total_amount' => 25000,
        'status' => 'baru',
        'progress' => 0,
    ]);

    KitchenOrderItem::create([
        'kitchen_order_id' => $kitchenOrder->id,
        'inventory_item_id' => $inventoryItem->id,
        'quantity' => 1,
        'price' => 25000,
        'is_completed' => false,
    ]);

    GeneralSetting::instance()->update([
        'can_choose_checker' => true,
    ]);

    return [
        'admin' => $admin,
        'order' => $order,
        'kitchenOrder' => $kitchenOrder,
        'checkerPrinterOne' => $checkerPrinterOne,
        'checkerPrinterTwo' => $checkerPrinterTwo,
    ];
}

test('checker print requires selected checker printers when can_choose_checker is enabled and multiple checker printers are assigned', function () {
    $fixture = createCheckerSelectionFixture();

    mock(PrinterService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('printCheckerTicket')->never();
    });

    actingAs($fixture['admin'])
        ->postJson(route('admin.pos.print-receipt', $fixture['order']), [
            'type' => 'checker',
        ])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Pilih minimal satu printer checker.');
});

test('checker print sends ticket only to selected checker printers when can_choose_checker is enabled', function () {
    $fixture = createCheckerSelectionFixture();

    mock(PrinterService::class, function (MockInterface $mock) use ($fixture): void {
        $mock->shouldReceive('printCheckerTicket')
            ->once()
            ->withArgs(fn ($kitchenOrderArg, Printer $printerArg): bool => (int) $kitchenOrderArg->id === (int) $fixture['kitchenOrder']->id
                && (int) $printerArg->id === (int) $fixture['checkerPrinterTwo']->id)
            ->andReturnTrue();
    });

    actingAs($fixture['admin'])
        ->postJson(route('admin.pos.print-receipt', $fixture['order']), [
            'type' => 'checker',
            'checker_printer_ids' => [$fixture['checkerPrinterTwo']->id],
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);
});
