<?php

use App\Models\InternalUser;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AccurateService;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

test('sync accurate employees creates internal users in user management', function () {
    $admin = adminUser();
    Role::firstOrCreate(['name' => 'Cashier']);

    mock(AccurateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getEmployees')
            ->once()
            ->andReturn(collect([
                [
                    'id' => 9001,
                    'name' => 'Employee Accurate 1',
                    'email' => 'accurate.employee1@example.com',
                    'position' => 'Cashier',
                    'mobilePhone' => '081234567890',
                ],
            ]));
    });

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->post(route('admin.users.sync-accurate'))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success');

    $user = User::query()->where('email', 'accurate.employee1@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user?->name)->toBe('Employee Accurate 1')
        ->and($user?->hasRole('Cashier'))->toBeTrue();

    $profile = UserProfile::query()->where('user_id', $user?->id)->first();
    $internal = InternalUser::query()->where('user_id', $user?->id)->first();

    expect($profile)->not->toBeNull()
        ->and($profile?->phone)->toBe('081234567890')
        ->and($internal)->not->toBeNull()
        ->and((int) ($internal?->accurate_id ?? 0))->toBe(9001)
        ->and((bool) ($internal?->is_active ?? false))->toBeTrue();
});

test('sync accurate employees updates existing internal user by accurate id', function () {
    $admin = adminUser();
    Role::firstOrCreate(['name' => 'Cashier']);

    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old.employee@example.com',
    ]);

    $profile = UserProfile::create([
        'user_id' => $user->id,
        'phone' => '081100000000',
    ]);

    InternalUser::create([
        'user_id' => $user->id,
        'user_profile_id' => $profile->id,
        'accurate_id' => 9002,
        'is_active' => true,
    ]);

    mock(AccurateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getEmployees')
            ->once()
            ->andReturn(collect([
                [
                    'id' => 9002,
                    'name' => 'Updated From Accurate',
                    'email' => 'updated.employee@example.com',
                    'position' => 'Cashier',
                    'mobilePhone' => '082233344455',
                ],
            ]));
    });

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->post(route('admin.users.sync-accurate'))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success');

    $user->refresh();
    $profile->refresh();

    expect($user->name)->toBe('Updated From Accurate')
        ->and($user->email)->toBe('updated.employee@example.com')
        ->and($profile->phone)->toBe('082233344455')
        ->and($user->hasRole('Cashier'))->toBeTrue();
});

test('sync accurate employees returns json response for async request', function () {
    $admin = adminUser();
    Role::firstOrCreate(['name' => 'Cashier']);

    mock(AccurateService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getEmployees')
            ->once()
            ->andReturn(collect([
                [
                    'id' => 9003,
                    'name' => 'Async Accurate Employee',
                    'email' => 'accurate.employee3@example.com',
                    'position' => 'Cashier',
                ],
            ]));
    });

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->postJson(route('admin.users.sync-accurate'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Sync employee Accurate berhasil. Baru: 1, Update: 0, Lewati: 0.')
        ->assertJsonPath('output', "Baru: 1\nUpdate: 0\nLewati: 0");
});
