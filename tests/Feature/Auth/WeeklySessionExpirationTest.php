<?php

use App\Http\Middleware\EnforceWeeklyLogin;
use App\Models\User;
use Carbon\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

function setWibTime(string $time): void
{
    Carbon::setTestNow(Carbon::parse($time, 'Asia/Jakarta'));
}

it('keeps an authenticated session active during the same weekly cycle', function () {
    setWibTime('2026-09-01 08:00:00');
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSessionHas(EnforceWeeklyLogin::SESSION_KEY, '2026-08-30');

    setWibTime('2026-09-05 23:59:00');

    $this->get(route('dashboard'))->assertOk();
    $this->assertAuthenticatedAs($user);
});

it('logs the user out when Sunday starts a new weekly cycle', function () {
    setWibTime('2026-09-05 23:59:00');
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSessionHas(EnforceWeeklyLogin::SESSION_KEY, '2026-08-30');

    setWibTime('2026-09-06 00:00:00');

    $this->get(route('dashboard'))
        ->assertRedirect(route('login', ['expired' => 1]))
        ->assertSessionHas('status', EnforceWeeklyLogin::EXPIRED_MESSAGE);

    $this->assertGuest();
});

it('resets at midnight WIB even though the application clock is still Saturday in UTC', function () {
    setWibTime('2026-09-05 22:00:00');
    $this->actingAs(User::factory()->create());
    $this->get(route('dashboard'))->assertOk();

    // Minggu 06:00 WIB = Sabtu 23:00 UTC.
    setWibTime('2026-09-06 06:00:00');

    $this->get(route('dashboard'))->assertRedirect(route('login', ['expired' => 1]));
    $this->assertGuest();
});

it('shows the weekly expiry message on the login page even without the flash message', function () {
    $this->get(route('login', ['expired' => 1]))
        ->assertOk()
        ->assertSee(EnforceWeeklyLogin::EXPIRED_MESSAGE);
});
