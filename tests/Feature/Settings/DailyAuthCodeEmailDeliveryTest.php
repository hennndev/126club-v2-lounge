<?php

use App\Mail\DailyAuthCodeDeliveryMail;
use App\Models\DailyAuthCode;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\actingAs;

test('daily auth code can be sent to configured target email', function () {
    $admin = adminUser();

    GeneralSetting::instance()->update([
        'auth_code_target_email' => 'approval@company.test',
    ]);

    DailyAuthCode::forDate(now()->format('Y-m-d'))->update([
        'code' => '1234',
        'override_code' => null,
    ]);

    Mail::fake();

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->postJson(route('admin.settings.daily-auth-code.send-email'))
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    Mail::assertSent(DailyAuthCodeDeliveryMail::class, function (DailyAuthCodeDeliveryMail $mail): bool {
        return $mail->hasTo('approval@company.test') && $mail->code === '1234';
    });
});

test('daily auth code email request fails when target email is not configured', function () {
    $admin = adminUser();

    GeneralSetting::instance()->update([
        'auth_code_target_email' => null,
    ]);

    Mail::fake();

    actingAs($admin)
        ->withSession(['accurate_database' => 'test'])
        ->postJson(route('admin.settings.daily-auth-code.send-email'))
        ->assertUnprocessable()
        ->assertJson([
            'success' => false,
        ]);

    Mail::assertNothingSent();
});
