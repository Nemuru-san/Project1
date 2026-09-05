<?php

use App\Http\Middleware\EnforceWeeklyLogin;
use App\Models\User;
use Carbon\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('keeps an authenticated session active during the same weekly cycle', function () {
    Carbon::setTestNow('2026-09-01 08:00:00');
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSessionHas(EnforceWeeklyLogin::SESSION_KEY, '2026-08-30');

    Carbon::setTestNow('2026-09-05 23:59:00');

    $this->get(route('dashboard'))->assertOk();
    $this->assertAuthenticatedAs($user);
});

it('logs the user out when Sunday starts a new weekly cycle', function () {
    Carbon::setTestNow('2026-09-05 23:59:00');
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSessionHas(EnforceWeeklyLogin::SESSION_KEY, '2026-08-30');

    Carbon::setTestNow('2026-09-06 00:00:00');

    $this->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Sesi mingguan telah berakhir. Silakan login kembali.');

    $this->assertGuest();
});
