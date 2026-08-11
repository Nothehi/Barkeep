<?php

use Modules\Identity\Domain\Models\User;

test('guests cannot reach the profile', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
    $this->patch(route('profile.update'), [
        'name' => 'Ada Designer',
        'email' => 'ada@barkeep.test',
    ])->assertRedirect(route('login'));
});

test('an account may update its own profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Ada Designer',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh()->name)->toBe('Ada Designer');
});

test('a profile update normalises the email address', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => '  Ada@Barkeep.TEST ',
    ])->assertSessionHasNoErrors();

    expect($user->fresh()->email)->toBe('ada@barkeep.test');
});

test('changing the email address requires verifying it again', function () {
    $user = User::factory()->create();

    expect($user->email_verified_at)->not->toBeNull();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => 'ada@barkeep.test',
    ]);

    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('an email address already in use is rejected', function () {
    User::factory()->create(['email' => 'taken@barkeep.test']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'taken@barkeep.test',
        ])
        ->assertSessionHasErrors('email');
});

test('an account may only update its own profile', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    expect($user->can('update', $user))->toBeTrue()
        ->and($user->can('update', $other))->toBeFalse()
        ->and($user->can('view', $other))->toBeFalse()
        ->and($user->can('delete', $other))->toBeFalse();
});
