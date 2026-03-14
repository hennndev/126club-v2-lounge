<?php

use App\Models\Area;
use App\Models\CustomerUser;
use App\Models\GeneralSetting;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\PosCategorySetting;
use App\Models\Printer;
use App\Models\Tabel;
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

    expect($inventoryItem->fresh()->stock_quantity)->toBe(7);
});

test('pos confirmation modal keeps loading state visible while checkout is processing', function () {
    $admin = adminUser();

    $response = actingAs($admin)->get(route('admin.pos.index'));

    $response->assertOk()
        ->assertSee('@click.self="if (!isProcessing) { showConfirmModal = false }"', false)
        ->assertSee('@click="submitCheckout()"', false)
        ->assertSee(':disabled="isProcessing"', false)
        ->assertSee('x-show="calculatedServiceCharge() > 0"', false)
        ->assertSee('x-show="calculatedTax() > 0"', false)
        ->assertDontSee('x-text="receiptData?.tableDisplay"', false)
        ->assertSee('Memproses...', false);
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
        $mock->shouldReceive('printReceipt')->once()->andReturnTrue();
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
        $mock->shouldReceive('printKitchenTicket')->once()->andReturnTrue();
        $mock->shouldReceive('printBarTicket')->never();
        $mock->shouldReceive('printReceipt')->once()->andReturnTrue();
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
        $mock->shouldReceive('printReceipt')->once()->andReturnTrue();
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
                    'number' => 'SO-WALKIN-001',
                ],
            ]);

        $mock->shouldReceive('saveSalesInvoice')
            ->once()
            ->andReturn([
                'r' => [
                    'number' => 'INV-WALKIN-001',
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

    expect($inventoryItem->fresh()->stock_quantity)->toBe(6)
        ->and($customerUser->fresh()->customer_code)->toBe('CUST-WALKIN-001')
        ->and($customerUser->fresh()->accurate_id)->toBe(98765)
        ->and($order)->not->toBeNull()
        ->and((float) $order->total)->toBe(61050.0)
        ->and($order->accurate_so_number)->toBe('SO-WALKIN-001')
        ->and($order->accurate_inv_number)->toBe('INV-WALKIN-001');
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

        $mock->shouldReceive('printReceipt')->once()->andReturnTrue();
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
            'discount_percentage' => 0,
        ])
        ->assertSuccessful()
        ->assertJsonPath('success', true);
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
