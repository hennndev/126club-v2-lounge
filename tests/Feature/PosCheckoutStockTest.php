<?php

use App\Models\Area;
use App\Models\Billing;
use App\Models\CustomerUser;
use App\Models\GeneralSetting;
use App\Models\InventoryItem;
use App\Models\KitchenOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PosCategorySetting;
use App\Models\Printer;
use App\Models\Tabel;
use App\Models\TableReservation;
use App\Models\TableSession;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AccurateService;
use App\Services\PrinterService;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

function makePosInventoryItem(array $attributes = []): InventoryItem
{
    return InventoryItem::create(array_merge([
        'code' => 'POS-ITEM-'.uniqid(),
        'accurate_id' => random_int(100000, 999999),
        'name' => 'POS Item '.uniqid(),
        'category_type' => 'beverage',
        'price' => 25000,
        'stock_quantity' => 20,
        'threshold' => 5,
        'unit' => 'glass',
        'is_active' => true,
    ], $attributes));
}

function makePosArea(): Area
{
    return Area::create([
        'code' => 'POS-AREA-'.uniqid(),
        'name' => 'POS Area '.uniqid(),
        'is_active' => true,
        'sort_order' => 1,
    ]);
}

function makePosTable(Area $area): Tabel
{
    return Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'POS-TBL-'.uniqid(),
        'qr_code' => 'POS-QR-'.uniqid(),
        'capacity' => 4,
        'minimum_charge' => 0,
        'status' => 'occupied',
        'is_active' => true,
    ]);
}

test('booking checkout decrements inventory stock', function () {
    $admin = adminUser();
    $customer = User::factory()->create();
    $area = makePosArea();
    $table = makePosTable($area);
    $inventoryItem = makePosInventoryItem(['stock_quantity' => 10]);

    GeneralSetting::instance()->update([
        'service_charge_percentage' => 10,
        'tax_percentage' => 11,
    ]);

    TableSession::create([
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SESSION-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    $cartKey = 'item_'.$inventoryItem->id;

    $response = actingAs($admin)
        ->withSession([
            'pos_cart' => [
                $cartKey => [
                    'id' => $cartKey,
                    'name' => $inventoryItem->name,
                    'price' => (float) $inventoryItem->price,
                    'quantity' => 3,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->postJson(route('admin.pos.checkout'), [
            'customer_type' => 'booking',
            'customer_user_id' => $customer->id,
            'table_id' => $table->id,
            'discount_percentage' => 0,
        ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    $orderItem = OrderItem::query()->latest('id')->first();

    expect($inventoryItem->fresh()->stock_quantity)->toBe(7)
        ->and($orderItem)->not->toBeNull()
        ->and((float) $orderItem->service_charge_amount)->toBe(7500.0)
        ->and((float) $orderItem->tax_amount)->toBe(9075.0);
});

test('booking checkout requires waiter assignment for reservation session', function () {
    $admin = adminUser();
    $customer = User::factory()->create();
    $area = makePosArea();
    $table = makePosTable($area);
    $inventoryItem = makePosInventoryItem(['stock_quantity' => 10]);

    $reservation = TableReservation::create([
        'booking_code' => random_int(100000, 999999),
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'reservation_date' => today(),
        'reservation_time' => now()->format('H:i:s'),
        'status' => 'checked_in',
    ]);

    TableSession::create([
        'table_reservation_id' => $reservation->id,
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SESSION-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
        'waiter_id' => null,
    ]);

    $cartKey = 'item_'.$inventoryItem->id;

    $response = actingAs($admin)
        ->withSession([
            'pos_cart' => [
                $cartKey => [
                    'id' => $cartKey,
                    'name' => $inventoryItem->name,
                    'price' => (float) $inventoryItem->price,
                    'quantity' => 1,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->postJson(route('admin.pos.checkout'), [
            'customer_type' => 'booking',
            'customer_user_id' => $customer->id,
            'table_id' => $table->id,
            'discount_percentage' => 0,
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Pilih waiter terlebih dahulu sebelum menyelesaikan transaksi.');
});

test('pos confirmation modal keeps loading state visible while checkout is processing', function () {
    $admin = adminUser();

    $response = actingAs($admin)->get(route('admin.pos.index'));

    $response->assertOk()
        ->assertSee('@click.self="if (!isProcessing) { showConfirmModal = false }"', false)
        ->assertSee('@click="submitCheckout()"', false)
        ->assertSee(':disabled="isProcessing"', false)
        ->assertSee('Discount (Opsional)', false)
        ->assertSee('Split Bill', false)
        ->assertSee('Auth Code Diskon (4 digit)', false)
        ->assertSee('x-show="calculatedServiceCharge() > 0"', false)
        ->assertSee('x-show="calculatedTax() > 0"', false)
        ->assertDontSee('x-text="receiptData?.tableDisplay"', false)
        ->assertDontSee('<span class="text-xs font-semibold text-green-600">Meja</span>', false)
        ->assertSee('Memproses...', false);
});

test('walk in checkout split payment must match grand total', function () {
    $admin = adminUser();
    $customer = User::factory()->create();
    $profile = UserProfile::create([
        'user_id' => $customer->id,
        'phone' => '081999999999',
    ]);

    CustomerUser::create([
        'user_id' => $customer->id,
        'user_profile_id' => $profile->id,
        'accurate_id' => null,
        'customer_code' => null,
        'total_visits' => 0,
        'lifetime_spending' => 0,
    ]);

    GeneralSetting::instance()->update([
        'service_charge_percentage' => 10,
        'tax_percentage' => 11,
    ]);

    $inventoryItem = makePosInventoryItem(['stock_quantity' => 8]);
    $cartKey = 'item_'.$inventoryItem->id;

    $response = actingAs($admin)
        ->withSession([
            'pos_cart' => [
                $cartKey => [
                    'id' => $cartKey,
                    'name' => $inventoryItem->name,
                    'price' => (float) $inventoryItem->price,
                    'quantity' => 2,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->postJson(route('admin.pos.checkout'), [
            'customer_type' => 'walk-in',
            'walk_in_customer_id' => $customer->id,
            'payment_mode' => 'split',
            'split_cash_amount' => 10000,
            'split_non_cash_amount' => 10000,
            'split_non_cash_method' => 'debit',
            'split_non_cash_reference_number' => 'SPLIT-REF-001',
            'discount_type' => 'none',
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Total split (cash + non-cash) harus sama dengan grand total.');
});

test('booking checkout auto prints one menu to multiple assigned target printers', function () {
    $admin = adminUser();
    $customer = User::factory()->create();
    $area = makePosArea();
    $table = makePosTable($area);

    GeneralSetting::instance()->update([
        'service_charge_percentage' => 10,
        'tax_percentage' => 11,
    ]);

    $targetPrinterOne = Printer::create([
        'name' => 'Kitchen Target A',
        'location' => 'kitchen',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_active' => true,
    ]);

    $targetPrinterTwo = Printer::create([
        'name' => 'Kitchen Target B',
        'location' => 'kitchen',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_active' => true,
    ]);

    $cashierPrinter = Printer::create([
        'name' => 'Cashier Default',
        'location' => 'cashier',
        'printer_type' => 'cashier',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => true,
        'is_active' => true,
    ]);

    GeneralSetting::instance()->update([
        'walk_in_receipt_printer_id' => $cashierPrinter->id,
    ]);

    $inventoryItem = makePosInventoryItem(['stock_quantity' => 10, 'category_type' => 'main-course']);
    $inventoryItem->printers()->sync([$targetPrinterOne->id, $targetPrinterTwo->id]);

    PosCategorySetting::updateOrCreate(
        ['category_type' => 'main-course'],
        [
            'show_in_pos' => true,
            'is_menu' => true,
            'is_item_group' => false,
            'preparation_location' => 'kitchen',
            'source' => 'inventory',
        ]
    );

    TableSession::create([
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SESSION-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    mock(PrinterService::class, function (MockInterface $mock) use ($targetPrinterOne, $targetPrinterTwo): void {
        $mock->shouldReceive('printKitchenTicket')
            ->twice()
            ->withArgs(function ($order, $printer) use ($targetPrinterOne, $targetPrinterTwo): bool {
                if (! in_array($printer->id, [$targetPrinterOne->id, $targetPrinterTwo->id], true)) {
                    return false;
                }

                return (int) ($order->items->count() ?? 0) === 1;
            })
            ->andReturnTrue();

        $mock->shouldReceive('printBarTicket')->never();
        $mock->shouldReceive('printReceipt')->never();
    });

    $cartKey = 'item_'.$inventoryItem->id;

    actingAs($admin)
        ->withSession([
            'pos_cart' => [
                $cartKey => [
                    'id' => $cartKey,
                    'name' => $inventoryItem->name,
                    'price' => (float) $inventoryItem->price,
                    'quantity' => 1,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->postJson(route('admin.pos.checkout'), [
            'customer_type' => 'booking',
            'customer_user_id' => $customer->id,
            'table_id' => $table->id,
            'discount_percentage' => 0,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('receipt_printed', false);
});

test('booking checkout returns tax and service totals based on menu flags', function () {
    $admin = adminUser();
    $customer = User::factory()->create();
    $area = makePosArea();
    $table = makePosTable($area);

    GeneralSetting::instance()->update([
        'service_charge_percentage' => 10,
        'tax_percentage' => 11,
    ]);

    Printer::create([
        'name' => 'Cashier Default',
        'location' => 'cashier',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => true,
        'is_active' => true,
    ]);

    $inventoryItem = makePosInventoryItem([
        'stock_quantity' => 10,
        'include_tax' => true,
        'include_service_charge' => false,
    ]);

    TableSession::create([
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SESSION-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    mock(PrinterService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('printKitchenTicket')->never();
        $mock->shouldReceive('printBarTicket')->never();
        $mock->shouldReceive('printReceipt')->never();
    });

    $cartKey = 'item_'.$inventoryItem->id;

    $response = actingAs($admin)
        ->withSession([
            'pos_cart' => [
                $cartKey => [
                    'id' => $cartKey,
                    'name' => $inventoryItem->name,
                    'price' => (float) $inventoryItem->price,
                    'quantity' => 2,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->postJson(route('admin.pos.checkout'), [
            'customer_type' => 'booking',
            'customer_user_id' => $customer->id,
            'table_id' => $table->id,
            'discount_percentage' => 0,
        ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('items_total', 50000)
        ->assertJsonPath('service_charge_percentage', 10)
        ->assertJsonPath('service_charge', 0)
        ->assertJsonPath('tax_percentage', 11)
        ->assertJsonPath('tax', 5500)
        ->assertJsonPath('total', 55500);
});

test('booking checkout keeps printing to other assigned printers when one target printer fails', function () {
    $admin = adminUser();
    $customer = User::factory()->create();
    $area = makePosArea();
    $table = makePosTable($area);

    $networkPrinter = Printer::create([
        'name' => 'Kitchen Network Fail',
        'location' => 'kitchen',
        'connection_type' => 'network',
        'ip' => '10.10.10.10',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_active' => true,
    ]);

    $logPrinter = Printer::create([
        'name' => 'Kitchen Log Success',
        'location' => 'kitchen',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_active' => true,
    ]);

    Printer::create([
        'name' => 'Cashier Default',
        'location' => 'cashier',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => true,
        'is_active' => true,
    ]);

    PosCategorySetting::updateOrCreate(
        ['category_type' => 'main-course'],
        [
            'show_in_pos' => true,
            'is_menu' => true,
            'is_item_group' => false,
            'preparation_location' => 'kitchen',
            'source' => 'inventory',
        ]
    );

    $inventoryItem = makePosInventoryItem(['stock_quantity' => 10, 'category_type' => 'main-course']);
    $inventoryItem->printers()->sync([$networkPrinter->id, $logPrinter->id]);

    TableSession::create([
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SESSION-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    mock(PrinterService::class, function (MockInterface $mock) use ($networkPrinter, $logPrinter): void {
        $mock->shouldReceive('printKitchenTicket')
            ->twice()
            ->andReturnUsing(function ($order, $printer) use ($networkPrinter, $logPrinter): bool {
                expect((int) ($order->items->count() ?? 0))->toBe(1);

                if ($printer->id === $networkPrinter->id) {
                    throw new RuntimeException('Network printer unreachable');
                }

                expect($printer->id)->toBe($logPrinter->id);

                return true;
            });

        $mock->shouldReceive('printBarTicket')->never();
        $mock->shouldReceive('printReceipt')->never();
    });

    $cartKey = 'item_'.$inventoryItem->id;

    actingAs($admin)
        ->withSession([
            'pos_cart' => [
                $cartKey => [
                    'id' => $cartKey,
                    'name' => $inventoryItem->name,
                    'price' => (float) $inventoryItem->price,
                    'quantity' => 1,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->postJson(route('admin.pos.checkout'), [
            'customer_type' => 'booking',
            'customer_user_id' => $customer->id,
            'table_id' => $table->id,
            'discount_percentage' => 0,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);
});

test('walk in checkout decrements inventory stock and syncs accurate documents', function () {
    $admin = adminUser();
    GeneralSetting::instance()->update([
        'service_charge_percentage' => 10,
        'tax_percentage' => 11,
    ]);

    $customer = User::factory()->create();
    $profile = UserProfile::create([
        'user_id' => $customer->id,
        'phone' => '08123456789',
    ]);

    $customerUser = CustomerUser::create([
        'user_id' => $customer->id,
        'user_profile_id' => $profile->id,
        'accurate_id' => null,
        'customer_code' => null,
        'total_visits' => 0,
        'lifetime_spending' => 0,
    ]);

    mock(AccurateService::class, function (MockInterface $mock): void {
        // Item has no group components → decrement item's own stock
        $mock->shouldReceive('getItemGroupComponents')
            ->andReturn([]);

        $mock->shouldReceive('saveCustomer')
            ->once()
            ->andReturn([
                'r' => [
                    'id' => 98765,
                    'customerNo' => 'CUST-WALKIN-001',
                ],
            ]);

        $mock->shouldReceive('saveSalesOrder')
            ->once()
            ->andReturn([
                'r' => [
                    'number' => 'ROOM-WALKIN-20260318-0001',
                ],
            ]);

        $mock->shouldReceive('saveSalesInvoice')
            ->once()
            ->andReturn([
                'r' => [
                    'number' => 'ROOM-WALKIN-20260318-0001',
                ],
            ]);
    });

    $inventoryItem = makePosInventoryItem(['stock_quantity' => 8]);
    $cartKey = 'item_'.$inventoryItem->id;

    $response = actingAs($admin)
        ->withSession([
            'pos_cart' => [
                $cartKey => [
                    'id' => $cartKey,
                    'name' => $inventoryItem->name,
                    'price' => (float) $inventoryItem->price,
                    'quantity' => 2,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->postJson(route('admin.pos.checkout'), [
            'customer_type' => 'walk-in',
            'walk_in_customer_id' => $customer->id,
            'payment_method' => 'transfer',
            'payment_mode' => 'normal',
            'payment_reference_number' => 'TRF-REF-001',
            'discount_percentage' => 0,
        ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('items_total', 50000)
        ->assertJsonPath('service_charge_percentage', 10)
        ->assertJsonPath('service_charge', 5000)
        ->assertJsonPath('tax_percentage', 11)
        ->assertJsonPath('tax', 6050)
        ->assertJsonPath('total', 61050);

    $order = Order::query()->latest('id')->first();
    $billing = Billing::query()->where('order_id', $order?->id)->first();

    expect($inventoryItem->fresh()->stock_quantity)->toBe(6)
        ->and($customerUser->fresh()->customer_code)->toBe('CUST-WALKIN-001')
        ->and($customerUser->fresh()->accurate_id)->toBe(98765)
        ->and($order)->not->toBeNull()
        ->and($billing)->not->toBeNull()
        ->and((float) $order->total)->toBe(61050.0)
        ->and((bool) $billing->is_walk_in)->toBeTrue()
        ->and((bool) $billing->is_booking)->toBeFalse()
        ->and((float) $billing->grand_total)->toBe(61050.0)
        ->and((float) $billing->tax)->toBe(6050.0)
        ->and((float) $billing->service_charge)->toBe(5000.0)
        ->and((float) $order->items()->latest('id')->first()->service_charge_amount)->toBe(5000.0)
        ->and((float) $order->items()->latest('id')->first()->tax_amount)->toBe(6050.0)
        ->and($order->payment_method)->toBe('transfer')
        ->and($order->payment_mode)->toBe('normal')
        ->and((string) $billing->transaction_code)->toMatch('/^WALKIN-\d{6}$/')
        ->and((string) $order->accurate_so_number)->toMatch('/^ROOM-WALKIN-\d{8}-\d{4}$/')
        ->and((string) $order->accurate_inv_number)->toMatch('/^ROOM-WALKIN-\d{8}-\d{4}$/');
});

test('walk in checkout auto prints one menu to multiple assigned target printers', function () {
    $admin = adminUser();
    $customer = User::factory()->create();
    $profile = UserProfile::create([
        'user_id' => $customer->id,
        'phone' => '081111111111',
    ]);

    CustomerUser::create([
        'user_id' => $customer->id,
        'user_profile_id' => $profile->id,
        'accurate_id' => null,
        'customer_code' => null,
        'total_visits' => 0,
        'lifetime_spending' => 0,
    ]);

    $targetPrinterOne = Printer::create([
        'name' => 'Walkin Target A',
        'location' => 'kitchen',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_active' => true,
    ]);

    $targetPrinterTwo = Printer::create([
        'name' => 'Walkin Target B',
        'location' => 'kitchen',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_active' => true,
    ]);

    $cashierPrinter = Printer::create([
        'name' => 'Cashier Default',
        'location' => 'cashier',
        'printer_type' => 'cashier',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => true,
        'is_active' => true,
    ]);

    $inventoryItem = makePosInventoryItem(['stock_quantity' => 8, 'category_type' => 'main-course']);
    $inventoryItem->printers()->sync([$targetPrinterOne->id, $targetPrinterTwo->id]);

    PosCategorySetting::updateOrCreate(
        ['category_type' => 'main-course'],
        [
            'show_in_pos' => true,
            'is_menu' => true,
            'is_item_group' => false,
            'preparation_location' => 'kitchen',
            'source' => 'inventory',
        ]
    );

    mock(AccurateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getItemGroupComponents')->andReturn([]);
        $mock->shouldReceive('saveCustomer')->andReturn(['r' => ['id' => 1, 'customerNo' => 'CUST-001']]);
        $mock->shouldReceive('saveSalesOrder')->andReturn(['r' => ['number' => 'SO-001']]);
        $mock->shouldReceive('saveSalesInvoice')->andReturn(['r' => ['number' => 'INV-001']]);
    });

    mock(PrinterService::class, function (MockInterface $mock) use ($targetPrinterOne, $targetPrinterTwo): void {
        $mock->shouldReceive('printKitchenTicket')
            ->twice()
            ->withArgs(function ($order, $printer) use ($targetPrinterOne, $targetPrinterTwo): bool {
                if (! in_array($printer->id, [$targetPrinterOne->id, $targetPrinterTwo->id], true)) {
                    return false;
                }

                return (int) ($order->items->count() ?? 0) === 1;
            })
            ->andReturnTrue();

        $mock->shouldReceive('printWalkInBillingReceipt')
            ->once()
            ->andReturnTrue();

        $mock->shouldReceive('printReceipt')->never();
        $mock->shouldReceive('printBarTicket')->never();
    });

    $cartKey = 'item_'.$inventoryItem->id;

    actingAs($admin)
        ->withSession([
            'pos_cart' => [
                $cartKey => [
                    'id' => $cartKey,
                    'name' => $inventoryItem->name,
                    'price' => (float) $inventoryItem->price,
                    'quantity' => 1,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->postJson(route('admin.pos.checkout'), [
            'customer_type' => 'walk-in',
            'walk_in_customer_id' => $customer->id,
            'payment_method' => 'cash',
            'payment_mode' => 'normal',
            'discount_percentage' => 0,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('receipt_printed', true);
});

test('booking checkout does not print receipt even when cashier printer exists', function () {
    $admin = adminUser();
    $customer = User::factory()->create();
    $area = makePosArea();
    $table = makePosTable($area);
    $inventoryItem = makePosInventoryItem(['stock_quantity' => 10]);

    Printer::create([
        'name' => 'Default Kitchen Printer',
        'location' => 'kitchen',
        'printer_type' => 'kitchen',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => true,
        'is_active' => true,
    ]);

    $cashierPrinter = Printer::create([
        'name' => 'Cashier Network Printer',
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

    TableSession::create([
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SESSION-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    mock(PrinterService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('printKitchenTicket')->never();
        $mock->shouldReceive('printBarTicket')->never();
        $mock->shouldReceive('printReceipt')->never();
    });

    $cartKey = 'item_'.$inventoryItem->id;

    actingAs($admin)
        ->withSession([
            'pos_cart' => [
                $cartKey => [
                    'id' => $cartKey,
                    'name' => $inventoryItem->name,
                    'price' => (float) $inventoryItem->price,
                    'quantity' => 1,
                    'preparation_location' => null,
                ],
            ],
        ])
        ->postJson(route('admin.pos.checkout'), [
            'customer_type' => 'booking',
            'customer_user_id' => $customer->id,
            'table_id' => $table->id,
            'discount_percentage' => 0,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('receipt_printed', false);
});

test('booking checkout for menu category decrements ingredient stock without decrementing menu stock', function () {
    $admin = adminUser();
    $customer = User::factory()->create();
    $area = makePosArea();
    $table = makePosTable($area);

    PosCategorySetting::create([
        'category_type' => 'main-course',
        'show_in_pos' => true,
        'is_menu' => true,
        'is_item_group' => true,
        'preparation_location' => 'kitchen',
    ]);

    $menuItem = makePosInventoryItem([
        'accurate_id' => 1901,
        'category_type' => 'main-course',
        'stock_quantity' => 10,
    ]);

    $ingredientItem = makePosInventoryItem([
        'accurate_id' => 2901,
        'category_type' => 'ingredient',
        'stock_quantity' => 20,
    ]);

    TableSession::create([
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SESSION-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    mock(AccurateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getItemGroupComponents')
            ->once()
            ->with(1901)
            ->andReturn([
                [
                    'itemId' => 2901,
                    'quantity' => 2,
                ],
            ]);
    });

    $cartKey = 'item_'.$menuItem->id;

    $response = actingAs($admin)
        ->withSession([
            'pos_cart' => [
                $cartKey => [
                    'id' => $cartKey,
                    'name' => $menuItem->name,
                    'price' => (float) $menuItem->price,
                    'quantity' => 3,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->postJson(route('admin.pos.checkout'), [
            'customer_type' => 'booking',
            'customer_user_id' => $customer->id,
            'table_id' => $table->id,
            'discount_percentage' => 0,
        ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect($menuItem->fresh()->stock_quantity)->toBe(10)
        ->and($ingredientItem->fresh()->stock_quantity)->toBe(14);
});

test('menu category item can be added to cart even when sold item stock is zero', function () {
    $admin = adminUser();

    PosCategorySetting::create([
        'category_type' => 'Main Course',
        'show_in_pos' => true,
        'is_menu' => true,
        'is_item_group' => true,
        'preparation_location' => 'kitchen',
    ]);

    $menuItem = makePosInventoryItem([
        'category_type' => 'Main Course',
        'stock_quantity' => 0,
    ]);

    $response = actingAs($admin)->postJson(route('admin.pos.add-to-cart', [
        'productId' => 'item_'.$menuItem->id,
    ]));

    $response
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('cart.0.id', 'item_'.$menuItem->id)
        ->assertJsonPath('cart.0.quantity', 1);
});

test('detail group menu can be added to cart when sold item stock is zero', function () {
    $admin = adminUser();

    PosCategorySetting::create([
        'category_type' => 'main-course',
        'show_in_pos' => true,
        'is_menu' => true,
        'is_item_group' => false,
        'preparation_location' => 'kitchen',
    ]);

    $menuItem = makePosInventoryItem([
        'accurate_id' => 8201,
        'category_type' => 'main-course',
        'stock_quantity' => 0,
    ]);

    makePosInventoryItem([
        'accurate_id' => 9201,
        'stock_quantity' => 10,
    ]);

    mock(AccurateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getItemGroupComponents')
            ->once()
            ->with(8201)
            ->andReturn([
                [
                    'itemId' => 9201,
                    'quantity' => 2,
                ],
            ]);
    });

    actingAs($admin)
        ->postJson(route('admin.pos.add-to-cart', [
            'productId' => 'item_'.$menuItem->id,
        ]))
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('cart.0.id', 'item_'.$menuItem->id)
        ->assertJsonPath('cart.0.quantity', 1);
});

test('non menu detail group item can be added to cart when sold item stock is zero', function () {
    $admin = adminUser();

    PosCategorySetting::create([
        'category_type' => 'warehouse-group',
        'show_in_pos' => true,
        'is_menu' => false,
        'is_item_group' => false,
        'preparation_location' => 'bar',
    ]);

    $groupItem = makePosInventoryItem([
        'accurate_id' => 8401,
        'category_type' => 'warehouse-group',
        'stock_quantity' => 0,
    ]);

    makePosInventoryItem([
        'accurate_id' => 9401,
        'stock_quantity' => 10,
    ]);

    mock(AccurateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getItemGroupComponents')
            ->once()
            ->with(8401)
            ->andReturn([
                [
                    'itemId' => 9401,
                    'quantity' => 2,
                ],
            ]);
    });

    actingAs($admin)
        ->postJson(route('admin.pos.add-to-cart', [
            'productId' => 'item_'.$groupItem->id,
        ]))
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('cart.0.id', 'item_'.$groupItem->id)
        ->assertJsonPath('cart.0.quantity', 1);
});

test('checkout availability preview uses detail group possible portions when item group is disabled', function () {
    $admin = adminUser();

    PosCategorySetting::create([
        'category_type' => 'main-course',
        'show_in_pos' => true,
        'is_menu' => true,
        'is_item_group' => false,
        'preparation_location' => 'kitchen',
    ]);

    $menuItem = makePosInventoryItem([
        'accurate_id' => 8301,
        'category_type' => 'main-course',
        'stock_quantity' => 999,
    ]);

    makePosInventoryItem([
        'accurate_id' => 9301,
        'name' => 'Bahan A',
        'stock_quantity' => 5,
    ]);

    mock(AccurateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getItemGroupComponents')
            ->once()
            ->with(8301)
            ->andReturn([
                [
                    'itemId' => 9301,
                    'detailName' => 'Bahan A',
                    'quantity' => 2,
                ],
            ]);
    });

    $cartKey = 'item_'.$menuItem->id;

    actingAs($admin)
        ->withSession([
            'pos_cart' => [
                $cartKey => [
                    'id' => $cartKey,
                    'name' => $menuItem->name,
                    'price' => (float) $menuItem->price,
                    'quantity' => 3,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->getJson(route('admin.pos.preview-checkout-availability'))
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('can_checkout', false)
        ->assertJsonPath('menu_items.0.product_id', $cartKey)
        ->assertJsonPath('menu_items.0.possible_portions', 2)
        ->assertJsonPath('menu_items.0.is_available', false);
});

test('checkout availability preview does not block item group products', function () {
    $admin = adminUser();

    PosCategorySetting::create([
        'category_type' => 'main-course',
        'show_in_pos' => true,
        'is_menu' => true,
        'is_item_group' => true,
        'preparation_location' => 'kitchen',
    ]);

    $menuItem = makePosInventoryItem([
        'accurate_id' => 4101,
        'category_type' => 'main-course',
        'stock_quantity' => 0,
    ]);

    $cartKey = 'item_'.$menuItem->id;

    $response = actingAs($admin)
        ->withSession([
            'pos_cart' => [
                $cartKey => [
                    'id' => $cartKey,
                    'name' => $menuItem->name,
                    'price' => (float) $menuItem->price,
                    'quantity' => 2,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->getJson(route('admin.pos.preview-checkout-availability'));

    $response
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('can_checkout', true)
        ->assertJsonPath('menu_items', [])
        ->assertJsonPath('stock_issues', []);
});

test('booking checkout is not blocked by item group stock validation', function () {
    $admin = adminUser();
    $customer = User::factory()->create();
    $area = makePosArea();
    $table = makePosTable($area);

    PosCategorySetting::create([
        'category_type' => 'main-course',
        'show_in_pos' => true,
        'is_menu' => true,
        'is_item_group' => true,
        'preparation_location' => 'kitchen',
    ]);

    $menuItem = makePosInventoryItem([
        'accurate_id' => 6101,
        'category_type' => 'main-course',
        'stock_quantity' => 0,
    ]);

    makePosInventoryItem([
        'accurate_id' => 7101,
        'name' => 'Ayam Fillet',
        'stock_quantity' => 5,
    ]);

    TableSession::create([
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SESSION-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    mock(AccurateService::class, function (MockInterface $mock) use ($menuItem): void {
        $mock->shouldReceive('getItemGroupComponents')
            ->once()
            ->with((int) $menuItem->accurate_id)
            ->andReturn([
                [
                    'itemId' => 7101,
                    'detailName' => 'Ayam Fillet',
                    'quantity' => 2,
                ],
            ]);
    });

    $cartKey = 'item_'.$menuItem->id;

    $response = actingAs($admin)
        ->withSession([
            'pos_cart' => [
                $cartKey => [
                    'id' => $cartKey,
                    'name' => $menuItem->name,
                    'price' => (float) $menuItem->price,
                    'quantity' => 3,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->postJson(route('admin.pos.checkout'), [
            'customer_type' => 'booking',
            'customer_user_id' => $customer->id,
            'table_id' => $table->id,
            'discount_percentage' => 0,
        ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect(Order::query()->count())->toBe(1);
});

test('non menu item cannot be added to cart when stock is empty', function () {
    $admin = adminUser();

    PosCategorySetting::create([
        'category_type' => 'Beer Lounge',
        'show_in_pos' => true,
        'is_menu' => false,
        'preparation_location' => 'bar',
    ]);

    $stockItem = makePosInventoryItem([
        'category_type' => 'Beer Lounge',
        'stock_quantity' => 0,
    ]);

    $response = actingAs($admin)->postJson(route('admin.pos.add-to-cart', [
        'productId' => 'item_'.$stockItem->id,
    ]));

    $response
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Stok tidak mencukupi untuk item ini.');
});

test('booking checkout prints cashier ticket for items assigned to cashier printer', function () {
    $admin = adminUser();
    $customer = User::factory()->create();
    $area = makePosArea();
    $table = makePosTable($area);

    $cashierPrinter = Printer::create([
        'name' => 'Cashier Station',
        'printer_type' => 'cashier',
        'location' => 'cashier',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_default' => false,
        'is_active' => true,
    ]);

    $inventoryItem = makePosInventoryItem(['stock_quantity' => 10]);
    $inventoryItem->printers()->sync([$cashierPrinter->id]);

    TableSession::create([
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SESSION-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    mock(PrinterService::class, function (MockInterface $mock) use ($cashierPrinter): void {
        $mock->shouldReceive('printCashierTicket')
            ->once()
            ->withArgs(function ($order, $printer) use ($cashierPrinter): bool {
                return $printer->id === $cashierPrinter->id;
            })
            ->andReturnTrue();

        $mock->shouldReceive('printKitchenTicket')->never();
        $mock->shouldReceive('printBarTicket')->never();
        $mock->shouldReceive('printReceipt')->never();
    });

    $cartKey = 'item_'.$inventoryItem->id;

    actingAs($admin)
        ->withSession([
            'pos_cart' => [
                $cartKey => [
                    'id' => $cartKey,
                    'name' => $inventoryItem->name,
                    'price' => (float) $inventoryItem->price,
                    'quantity' => 2,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->postJson(route('admin.pos.checkout'), [
            'customer_type' => 'booking',
            'customer_user_id' => $customer->id,
            'table_id' => $table->id,
            'discount_percentage' => 0,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);
});

test('booking checkout creates only one kitchen order when item is assigned to kitchen and cashier printers', function () {
    $admin = adminUser();
    $customer = User::factory()->create();
    $area = makePosArea();
    $table = makePosTable($area);

    $kitchenPrinter = Printer::create([
        'name' => 'Kitchen Station',
        'printer_type' => 'kitchen',
        'location' => 'kitchen',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_active' => true,
    ]);

    $cashierPrinter = Printer::create([
        'name' => 'Cashier Station',
        'printer_type' => 'cashier',
        'location' => 'cashier',
        'connection_type' => 'log',
        'port' => 9100,
        'timeout' => 30,
        'header' => '126 Club',
        'footer' => 'Thank you',
        'width' => 42,
        'is_active' => true,
    ]);

    $inventoryItem = makePosInventoryItem(['stock_quantity' => 10]);
    $inventoryItem->printers()->sync([$kitchenPrinter->id, $cashierPrinter->id]);

    TableSession::create([
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SESSION-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);

    mock(PrinterService::class, function (MockInterface $mock) use ($kitchenPrinter, $cashierPrinter): void {
        $mock->shouldReceive('printKitchenTicket')
            ->once()
            ->withArgs(function ($order, $printer) use ($kitchenPrinter): bool {
                return $printer->id === $kitchenPrinter->id;
            })
            ->andReturnTrue();

        $mock->shouldReceive('printCashierTicket')
            ->once()
            ->withArgs(function ($order, $printer) use ($cashierPrinter): bool {
                return $printer->id === $cashierPrinter->id;
            })
            ->andReturnTrue();

        $mock->shouldReceive('printBarTicket')->never();
        $mock->shouldReceive('printReceipt')->never();
    });

    $cartKey = 'item_'.$inventoryItem->id;

    actingAs($admin)
        ->withSession([
            'pos_cart' => [
                $cartKey => [
                    'id' => $cartKey,
                    'name' => $inventoryItem->name,
                    'price' => (float) $inventoryItem->price,
                    'quantity' => 1,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->postJson(route('admin.pos.checkout'), [
            'customer_type' => 'booking',
            'customer_user_id' => $customer->id,
            'table_id' => $table->id,
            'discount_percentage' => 0,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect(KitchenOrder::query()->count())->toBe(1);
});
