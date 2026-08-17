<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Identity\Application\Queries\GetUserById;
use Modules\Identity\Domain\Models\User;

test('an account is identified by a uuid', function () {
    $user = User::factory()->create();

    expect($user->id)->toBeString()
        ->and($user->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
});

test('the authenticated account is shared with every page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.id', $user->id)
            ->where('auth.user.name', $user->name)
            ->where('auth.user.email', $user->email)
            ->where('auth.user.status', 'active')
            ->where('auth.user.two_factor_enabled', false),
        );
});

test('no authentication secret is ever shared with the client', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('auth.user.password')
            ->missing('auth.user.remember_token')
            ->missing('auth.user.two_factor_secret')
            ->missing('auth.user.two_factor_recovery_codes'),
        );
});

test('guests are shared no account at all', function () {
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.user', null));
});

test('an account can be resolved by its identifier', function () {
    $user = User::factory()->create();

    expect(app(GetUserById::class)->handle($user->id)?->id)->toBe($user->id)
        ->and(app(GetUserById::class)->handle('01930000-0000-7000-8000-000000000000'))->toBeNull();
});
