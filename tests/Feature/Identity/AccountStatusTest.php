<?php

use Modules\Identity\Application\Commands\ActivateUser;
use Modules\Identity\Application\Commands\DisableUser;
use Modules\Identity\Application\Commands\SuspendUser;
use Modules\Identity\Domain\Enums\UserStatus;
use Modules\Identity\Domain\Models\User;

test('an account is active when it is created', function () {
    $user = User::factory()->create();

    expect($user->status)->toBe(UserStatus::Active)
        ->and($user->canAuthenticate())->toBeTrue();
});

test('a blocked account cannot authenticate', function (string $state) {
    $user = User::factory()->{$state}()->create();

    $response = $this->from(route('login'))->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
})->with(['suspended', 'disabled']);

test('a blocked account is told why rather than being shown a credential error', function () {
    $user = User::factory()->suspended()->create();

    $this->from(route('login'))->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors([
        'email' => UserStatus::Suspended->deniedReason(),
    ]);
});

test('an existing session is terminated once the account is suspended', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    app(SuspendUser::class)->handle($user);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('suspending then reactivating an account restores access', function () {
    $user = User::factory()->create();

    app(SuspendUser::class)->handle($user);
    expect($user->fresh()->status)->toBe(UserStatus::Suspended);

    app(ActivateUser::class)->handle($user);
    expect($user->fresh()->status)->toBe(UserStatus::Active);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
});

test('disabling an account blocks it', function () {
    $user = User::factory()->create();

    app(DisableUser::class)->handle($user);

    expect($user->fresh()->status)->toBe(UserStatus::Disabled)
        ->and($user->fresh()->canAuthenticate())->toBeFalse();
});
