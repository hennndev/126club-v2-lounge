<?php

use App\Models\Area;
use App\Models\Tabel;
use App\Models\TableSession;
use App\Models\User;
use Illuminate\Support\Str;

test('table cannot be updated when it has an active table session', function () {
    $admin = adminUser();
    $customer = User::factory()->create();
    $area = Area::create([
        'code' => 'ROOM',
        'name' => 'Room',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'A1',
        'qr_code' => 'QR-'.strtoupper(Str::random(12)),
        'capacity' => 4,
        'minimum_charge' => 0,
        'status' => 'available',
        'is_active' => true,
    ]);

    TableSession::create([
        'table_id' => $table->id,
        'customer_id' => $customer->id,
        'session_code' => 'SES-'.strtoupper(Str::random(10)),
        'status' => 'active',
        'checked_in_at' => now(),
    ]);

    $response = $this->actingAs($admin)->from(route('admin.tables.index'))->put(route('admin.tables.update', $table), [
        'area_id' => $area->id,
        'table_number' => 'A1-EDIT',
        'capacity' => 8,
        'minimum_charge' => 50000,
        'status' => 'maintenance',
        'is_active' => false,
    ]);

    $response
        ->assertRedirect(route('admin.tables.index'))
        ->assertSessionHasErrors('error');

    $table->refresh();

    expect($table->table_number)->toBe('A1')
        ->and($table->capacity)->toBe(4)
        ->and($table->status)->toBe('available')
        ->and($table->is_active)->toBeTrue();
});

test('table can be updated when it has no active table session', function () {
    $admin = adminUser();
    $area = Area::create([
        'code' => 'BALCONY',
        'name' => 'Balcony',
        'is_active' => true,
        'sort_order' => 2,
    ]);
    $table = Tabel::create([
        'area_id' => $area->id,
        'table_number' => 'B1',
        'qr_code' => 'QR-'.strtoupper(Str::random(12)),
        'capacity' => 6,
        'minimum_charge' => 0,
        'status' => 'available',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->put(route('admin.tables.update', $table), [
        'area_id' => $area->id,
        'table_number' => 'B1-EDIT',
        'capacity' => 10,
        'minimum_charge' => 150000,
        'status' => 'maintenance',
        'is_active' => false,
    ]);

    $response
        ->assertRedirect(route('admin.tables.index'))
        ->assertSessionHas('success');

    $table->refresh();

    expect($table->table_number)->toBe('B1-EDIT')
        ->and($table->capacity)->toBe(10)
        ->and((float) $table->minimum_charge)->toBe(150000.0)
        ->and($table->status)->toBe('maintenance')
        ->and($table->is_active)->toBeFalse();
});
