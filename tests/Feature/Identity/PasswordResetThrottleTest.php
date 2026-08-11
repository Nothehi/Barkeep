<?php

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Modules\Identity\Domain\Models\User;

test('requesting a reset link does not reveal whether the address exists', function () {
    Notification::fake();

    $known = User::factory()->create();

    $forUnknown = $this->post(route('password.email'), ['email' => 'nobody@barkeep.test']);
    $forKnown = $this->post(route('password.email'), ['email' => $known->email]);

    expect($forUnknown->getStatusCode())->toBe($forKnown->getStatusCode())
        ->and($forUnknown->getSession()->get('status'))
        ->toBe($forKnown->getSession()->get('status'));

    $forUnknown->assertSessionHasNoErrors();
    $forKnown->assertSessionHasNoErrors();

    Notification::assertSentTimes(ResetPasswordNotification::class, 1);
});

test('a malformed address is still rejected', function () {
    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');
});

test('reset link requests are rate limited', function () {
    Notification::fake();

    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors();
    }

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasErrors('email');
});

test('the rate limit is scoped to the address being requested', function () {
    Notification::fake();

    $user = User::factory()->create();
    $other = User::factory()->create();

    foreach (range(1, 6) as $attempt) {
        $this->post(route('password.email'), ['email' => $user->email]);
    }

    $this->post(route('password.email'), ['email' => $other->email])
        ->assertSessionHasNoErrors();
});
