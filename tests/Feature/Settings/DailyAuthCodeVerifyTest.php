<?php

use App\Models\DailyAuthCode;
use App\Models\User;

test('verify returns valid true when code matches active code', function () {
    $user = User::factory()->create();
    $today = now()->format('Y-m-d');

    DailyAuthCode::create([
        'date' => $today,
        'code' => '1234',
        'generated_at' => now(),
    ]);

    $this->actingAs($user)
        ->withSession(['accurate_database' => 'test'])
        ->postJson(route('admin.settings.daily-auth-code.verify'), ['code' => '1234'])
        ->assertOk()
        ->assertJson(['valid' => true]);
});

test('verify returns valid false when code does not match', function () {
    $user = User::factory()->create();
    $today = now()->format('Y-m-d');

    DailyAuthCode::create([
        'date' => $today,
        'code' => '1234',
        'generated_at' => now(),
    ]);

    $this->actingAs($user)
        ->withSession(['accurate_database' => 'test'])
        ->postJson(route('admin.settings.daily-auth-code.verify'), ['code' => '9999'])
        ->assertOk()
        ->assertJson(['valid' => false]);
});

test('verify uses override code when set', function () {
    $user = User::factory()->create();
    $today = now()->format('Y-m-d');

    DailyAuthCode::create([
        'date' => $today,
        'code' => '1234',
        'override_code' => '5678',
        'generated_at' => now(),
    ]);

    $this->actingAs($user)
        ->withSession(['accurate_database' => 'test'])
        ->postJson(route('admin.settings.daily-auth-code.verify'), ['code' => '5678'])
        ->assertOk()
        ->assertJson(['valid' => true]);
});

test('verify requires exactly 4 digits', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['accurate_database' => 'test'])
        ->postJson(route('admin.settings.daily-auth-code.verify'), ['code' => '12'])
        ->assertUnprocessable();
});

test('verify requires authentication', function () {
    $this->postJson(route('admin.settings.daily-auth-code.verify'), ['code' => '1234'])
        ->assertUnauthorized();
});
