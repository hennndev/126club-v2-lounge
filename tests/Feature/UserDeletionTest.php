<?php

use App\Models\InternalUser;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AccurateService;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;

test('deleting internal user with null accurate id does not call accurate delete and still succeeds', function () {
    $admin = adminUser();

    $staff = User::factory()->create();
    $profile = UserProfile::create([
        'user_id' => $staff->id,
    ]);

    InternalUser::create([
        'user_id' => $staff->id,
        'user_profile_id' => $profile->id,
        'accurate_id' => null,
        'is_active' => true,
    ]);

    mock(AccurateService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('deleteEmployee');
    });

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->delete(route('admin.users.destroy', $staff))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('success', 'User berhasil dihapus');

    expect(User::query()->find($staff->id))->toBeNull();
});
