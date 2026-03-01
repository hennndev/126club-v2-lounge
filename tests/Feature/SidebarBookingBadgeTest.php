<?php

use App\Models\TableReservation;

it('returns 0 pending bookings for sidebar badge when none exist', function () {
    $count = TableReservation::where('status', 'pending')->count();

    expect($count)->toBe(0);
});
