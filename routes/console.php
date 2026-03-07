<?php

use App\Jobs\NightlyTableReset;
use App\Models\ClubOperatingHour;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generate a new random daily auth code every day at midnight
Schedule::command('auth:generate-daily-code')->dailyAt('00:00');

// Nightly table reset — runs every minute and dispatches when current time matches today's close_time
Schedule::call(function (): void {
    $today = now('Asia/Jakarta');
    // ClubOperatingHour uses 0=Sunday...6=Saturday (same as Carbon's dayOfWeek)
    $operatingHour = ClubOperatingHour::query()
        ->where('day_of_week', $today->dayOfWeek)
        ->where('is_open', true)
        ->first();

    if (! $operatingHour) {
        return;
    }

    [$closeHour, $closeMinute] = array_map('intval', explode(':', $operatingHour->close_time));
    [$openHour] = array_map('intval', explode(':', $operatingHour->open_time));

    $closeTime = $today->copy()->setTime($closeHour, $closeMinute, 0);

    // If close hour is earlier than open hour, the club closes past midnight
    if ($closeHour < $openHour) {
        $closeTime->addDay();
    }

    // Dispatch only within the current minute window
    if ((int) $today->diffInMinutes($closeTime, false) === 0) {
        NightlyTableReset::dispatch();
    }
})->everyMinute()->timezone('Asia/Jakarta')->name('nightly-table-reset')->withoutOverlapping();
