<?php

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Spatie\Permission\Models\Role;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(RouteServiceProvider::HOME);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('authenticated users visiting login page are redirected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/login');

    $response->assertRedirect(route('login.redirect'));
});

test('authenticated users visiting login page are redirected to role default route when configured', function () {
    $user = User::factory()->create();
    $user->forceFill(['type' => 'internal'])->save();

    $role = Role::query()->firstOrCreate(
        ['name' => 'Cashier', 'guard_name' => 'web'],
        ['default_redirect_route' => 'admin.pos.index']
    );

    $role->update(['default_redirect_route' => 'admin.pos.index']);
    $user->assignRole($role);

    $response = $this->actingAs($user)->get('/redirect-after-login');

    $response->assertRedirect(route('admin.pos.index'));
});

test('configured role redirect takes precedence over waiter fallback redirect', function () {
    $user = User::factory()->create();
    $user->forceFill(['type' => 'internal'])->save();

    $role = Role::query()->firstOrCreate(
        ['name' => 'Waiter/Server', 'guard_name' => 'web'],
        ['default_redirect_route' => 'admin.dashboard']
    );

    $role->update(['default_redirect_route' => 'admin.dashboard']);
    $user->assignRole($role);

    $response = $this->actingAs($user)->get('/redirect-after-login');

    $response->assertRedirect(route('admin.dashboard'));
});
