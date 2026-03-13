<?php

use App\Http\Controllers\Waiter\WaiterPosController;
use App\Models\Area;
use App\Models\CustomerUser;
use App\Models\InventoryItem;
use App\Models\KitchenOrder;
use App\Models\Order;
use App\Models\PosCategorySetting;
use App\Models\Tabel;
use App\Models\TableReservation;
use App\Models\TableSession;
use App\Models\User;
use App\Models\UserProfile;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

function posWaiter(string $name = 'Waiter'): User
{
    Role::firstOrCreate(['name' => 'Waiter/Server', 'guard_name' => 'web']);
    $user = User::factory()->create(['name' => $name]);
    $user->assignRole('Waiter/Server');

    return $user;
}

function posArea(): Area
{
    return Area::create([
        'code' => 'POS-'.uniqid(),
        'name' => 'POS Area '.uniqid(),
        'capacity' => 20,
        'is_active' => true,
        'sort_order' => 1,
    ]);
}

function posTable(Area $area, string $number): Tabel
{
    return Tabel::create([
        'area_id' => $area->id,
        'table_number' => $number,
        'qr_code' => 'QR-POS-'.$number.'-'.uniqid(),
        'capacity' => 4,
        'minimum_charge' => 0,
        'status' => 'occupied',
        'is_active' => true,
    ]);
}

function posSession(Tabel $table, User $customer, User $waiter, bool $asBooking = true): TableSession
{
    $reservationId = null;

    if ($asBooking) {
        $reservation = TableReservation::create([
            'booking_code' => random_int(100000, 999999),
            'table_id' => $table->id,
            'customer_id' => $customer->id,
            'reservation_date' => today(),
            'reservation_time' => now()->format('H:i:s'),
            'status' => 'checked_in',
        ]);

        $reservationId = $reservation->id;
    }

    return TableSession::create([
        'table_reservation_id' => $reservationId,
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'waiter_id' => $waiter->id,
        'session_code' => 'SES-POS-'.uniqid(),
        'checked_in_at' => now(),
        'status' => 'active',
    ]);
}

function posProduct(string $categoryType = 'food'): InventoryItem
{
    PosCategorySetting::clearCache();

    PosCategorySetting::firstOrCreate(
        ['category_type' => $categoryType],
        [
            'show_in_pos' => true,
            'is_menu' => false,
            'preparation_location' => $categoryType === 'food' ? 'kitchen' : 'bar',
            'source' => 'inventory',
        ]
    );

    PosCategorySetting::clearCache();

    return InventoryItem::create([
        'name' => 'Test '.ucfirst($categoryType).' Item '.uniqid(),
        'code' => 'TST-'.uniqid(),
        'accurate_id' => 'ACC-'.uniqid(),
        'category_type' => $categoryType,
        'price' => 50000,
        'stock_quantity' => 99,
        'is_active' => true,
    ]);
}

beforeEach(function () {
    PosCategorySetting::clearCache();
});

test('waiter can add a product to cart via waiter route', function () {
    $waiter = posWaiter();
    $product = posProduct('food');
    $productId = 'item_'.$product->id;

    actingAs($waiter)
        ->withSession(['accurate_database' => 'test'])
        ->post(route('waiter.pos.add-to-cart', $productId))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('cart.'.$productId.'.qty', 1);
});

test('waiter checkout creates order for their own session and clears cart', function () {
    $waiter = posWaiter();
    $customer = User::factory()->create();
    $area = posArea();
    $table = posTable($area, 'P-01');
    $session = posSession($table, $customer, $waiter);
    $product = posProduct('food');
    $productId = 'item_'.$product->id;

    $cartData = [
        $productId => [
            'id' => $productId,
            'name' => $product->name,
            'price' => 50000.00,
            'quantity' => 2,
            'preparation_location' => 'kitchen',
        ],
    ];

    actingAs($waiter)
        ->withSession([
            'accurate_database' => 'test',
            WaiterPosController::CART_KEY => $cartData,
        ])
        ->post(route('waiter.pos.checkout'), ['session_id' => $session->id])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Order::where('table_session_id', $session->id)->exists())->toBeTrue();
});

test('waiter checkout links kitchen order to customer user for booking session', function () {
    $waiter = posWaiter();
    $customer = User::factory()->create();
    $profile = UserProfile::create([
        'user_id' => $customer->id,
        'phone' => '081234567890',
    ]);
    $customerUser = CustomerUser::create([
        'user_id' => $customer->id,
        'user_profile_id' => $profile->id,
        'total_visits' => 0,
        'lifetime_spending' => 0,
    ]);

    $area = posArea();
    $table = posTable($area, 'P-11');
    $session = posSession($table, $customer, $waiter);
    $product = posProduct('food');
    $productId = 'item_'.$product->id;

    actingAs($waiter)
        ->withSession([
            'accurate_database' => 'test',
            WaiterPosController::CART_KEY => [
                $productId => [
                    'id' => $productId,
                    'name' => $product->name,
                    'price' => 50000,
                    'quantity' => 1,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->post(route('waiter.pos.checkout'), ['session_id' => $session->id])
        ->assertOk()
        ->assertJsonPath('success', true);

    $kitchenOrder = KitchenOrder::query()->latest('id')->first();

    expect($kitchenOrder)->not->toBeNull()
        ->and((int) $kitchenOrder->customer_user_id)->toBe((int) $customerUser->id);
});

test('waiter cannot checkout for a session belonging to another waiter', function () {
    $waiterA = posWaiter('Waiter A');
    $waiterB = posWaiter('Waiter B');
    $customer = User::factory()->create();
    $area = posArea();
    $table = posTable($area, 'P-02');
    $sessionB = posSession($table, $customer, $waiterB);
    $product = posProduct('food');
    $productId = 'item_'.$product->id;

    $cartData = [
        $productId => [
            'id' => $productId,
            'name' => $product->name,
            'price' => 50000.00,
            'quantity' => 1,
            'preparation_location' => 'kitchen',
        ],
    ];

    actingAs($waiterA)
        ->withSession([
            'accurate_database' => 'test',
            WaiterPosController::CART_KEY => $cartData,
        ])
        ->post(route('waiter.pos.checkout'), ['session_id' => $sessionB->id])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('waiter cannot checkout non booking session even if assigned', function () {
    $waiter = posWaiter();
    $customer = User::factory()->create();
    $area = posArea();
    $table = posTable($area, 'P-04');
    $nonBookingSession = posSession($table, $customer, $waiter, false);
    $product = posProduct('food');
    $productId = 'item_'.$product->id;

    actingAs($waiter)
        ->withSession([
            'accurate_database' => 'test',
            WaiterPosController::CART_KEY => [
                $productId => [
                    'id' => $productId,
                    'name' => $product->name,
                    'price' => 50000,
                    'quantity' => 1,
                    'preparation_location' => 'kitchen',
                ],
            ],
        ])
        ->post(route('waiter.pos.checkout'), ['session_id' => $nonBookingSession->id])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('waiter pos page lists products from pos category settings including custom types', function () {
    $waiter = posWaiter();
    $customer = User::factory()->create();
    $area = posArea();
    $table = posTable($area, 'P-03');
    posSession($table, $customer, $waiter);

    $customCategory = 'snack_special';
    $product = posProduct($customCategory);

    $response = actingAs($waiter)
        ->withSession(['accurate_database' => 'test'])
        ->get(route('waiter.pos'))
        ->assertOk();

    $products = collect($response->viewData('products'));

    expect($products->pluck('id')->all())->toContain('item_'.$product->id)
        ->and($products->where('id', 'item_'.$product->id)->first()['category'])->toBe($customCategory);
});

test('waiter add to cart blocks menu when possible portions are not sufficient', function () {
    $waiter = posWaiter();

    PosCategorySetting::updateOrCreate(
        ['category_type' => 'food'],
        [
            'show_in_pos' => true,
            'is_menu' => true,
            'preparation_location' => 'kitchen',
            'source' => 'inventory',
        ]
    );
    PosCategorySetting::clearCache();

    $menuItem = InventoryItem::create([
        'name' => 'Nasi Goreng Special',
        'code' => 'MENU-NG-001',
        'accurate_id' => 5001,
        'category_type' => 'food',
        'price' => 35000,
        'stock_quantity' => 999,
        'is_active' => true,
    ]);

    actingAs($waiter)
        ->withSession(['accurate_database' => 'test'])
        ->post(route('waiter.pos.add-to-cart', 'item_'.$menuItem->id))
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('possible_portions', 0);
});
