<?php

use App\Models\Event;

use function Pest\Laravel\actingAs;

test('events index shows newly created upcoming event', function () {
    $admin = adminUser();

    Event::create([
        'name' => 'Past Event Test',
        'slug' => 'past-event-test',
        'description' => 'Event lampau untuk pembanding',
        'start_date' => now()->subDays(5)->toDateString(),
        'end_date' => now()->subDays(3)->toDateString(),
        'start_time' => '18:00',
        'end_time' => '23:00',
        'is_active' => false,
        'price_adjustment_type' => 'fixed',
        'price_adjustment_value' => 100000,
    ]);

    Event::create([
        'name' => 'Upcoming Event Test',
        'slug' => 'upcoming-event-test',
        'description' => 'Event baru harus muncul di index',
        'start_date' => now()->addDays(2)->toDateString(),
        'end_date' => now()->addDays(3)->toDateString(),
        'start_time' => '20:00',
        'end_time' => '23:59',
        'is_active' => true,
        'price_adjustment_type' => 'percentage',
        'price_adjustment_value' => 15,
    ]);

    actingAs($admin)
        ->get(route('admin.events.index'))
        ->assertSuccessful()
        ->assertSee('Upcoming Event Test')
        ->assertSee('Past Event Test');
});

test('event store accepts checkbox is_active value and creates active event', function () {
    $admin = adminUser();

    actingAs($admin)
        ->post(route('admin.events.store'), [
            'name' => 'Checkbox Active Event',
            'description' => 'Event active dari checkbox',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'start_time' => '19:00',
            'end_time' => '22:00',
            'is_active' => 'on',
            'price_adjustment_type' => 'fixed',
            'price_adjustment_value' => 100000,
        ])
        ->assertRedirect(route('admin.events.index'));

    $event = Event::query()->where('name', 'Checkbox Active Event')->first();

    expect($event)->not->toBeNull()
        ->and((bool) $event->is_active)->toBeTrue();
});

test('event store treats missing is_active checkbox as false', function () {
    $admin = adminUser();

    actingAs($admin)
        ->post(route('admin.events.store'), [
            'name' => 'Checkbox Inactive Event',
            'description' => 'Event inactive saat checkbox tidak dipilih',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'start_time' => '19:00',
            'end_time' => '22:00',
            'price_adjustment_type' => 'percentage',
            'price_adjustment_value' => 5,
        ])
        ->assertRedirect(route('admin.events.index'));

    $event = Event::query()->where('name', 'Checkbox Inactive Event')->first();

    expect($event)->not->toBeNull()
        ->and((bool) $event->is_active)->toBeFalse();
});
