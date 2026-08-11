<?php

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Modules\Identity\Application\Commands\ActivateUser;
use Modules\Identity\Application\Commands\DisableUser;
use Modules\Identity\Application\Commands\SuspendUser;
use Modules\Identity\Domain\Events\PasswordReset;
use Modules\Identity\Domain\Events\UserActivated;
use Modules\Identity\Domain\Events\UserDisabled;
use Modules\Identity\Domain\Events\UserEmailVerified;
use Modules\Identity\Domain\Events\UserLoggedIn;
use Modules\Identity\Domain\Events\UserLoggedOut;
use Modules\Identity\Domain\Events\UserRegistered;
use Modules\Identity\Domain\Events\UserSuspended;
use Modules\Identity\Domain\Models\User;

test('registering an account announces it', function () {
    Event::fake([UserRegistered::class]);

    $this->post(route('register.store'), [
        'name' => 'Ada Designer',
        'email' => 'Ada@Barkeep.TEST',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->sole();

    Event::assertDispatched(UserRegistered::class, function (UserRegistered $event) use ($user) {
        return $event->userId === $user->id
            && $event->name === 'Ada Designer'
            && $event->email === 'ada@barkeep.test';
    });
});

test('registration normalises the email address', function () {
    $this->post(route('register.store'), [
        'name' => 'Ada Designer',
        'email' => '  Ada@Barkeep.TEST ',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::query()->sole()->email)->toBe('ada@barkeep.test');
});

test('an email address can only be registered once', function () {
    User::factory()->create(['email' => 'ada@barkeep.test']);

    $this->from(route('register'))->post(route('register.store'), [
        'name' => 'Ada Designer',
        'email' => 'ada@barkeep.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');

    expect(User::query()->count())->toBe(1);
});

test('logging in announces it and records the moment', function () {
    Event::fake([UserLoggedIn::class]);

    $user = User::factory()->create();

    expect($user->last_login_at)->toBeNull();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    expect($user->fresh()->last_login_at)->not->toBeNull();

    Event::assertDispatched(
        UserLoggedIn::class,
        fn (UserLoggedIn $event) => $event->userId === $user->id,
    );
});

test('logging out announces it', function () {
    Event::fake([UserLoggedOut::class]);

    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'));

    Event::assertDispatched(
        UserLoggedOut::class,
        fn (UserLoggedOut $event) => $event->userId === $user->id,
    );
});

test('verifying an email address announces it', function () {
    Event::fake([UserEmailVerified::class]);

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    ));

    Event::assertDispatched(
        UserEmailVerified::class,
        fn (UserEmailVerified $event) => $event->userId === $user->id,
    );
});

test('resetting a password announces it and invalidates remember tokens', function () {
    Notification::fake();
    Event::fake([PasswordReset::class]);

    $user = User::factory()->create();
    $rememberToken = $user->remember_token;

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasNoErrors();

        return true;
    });

    expect($user->fresh()->remember_token)->not->toBe($rememberToken);

    Event::assertDispatched(
        PasswordReset::class,
        fn (PasswordReset $event) => $event->userId === $user->id,
    );
});

test('account state changes announce themselves', function () {
    Event::fake([UserSuspended::class, UserActivated::class, UserDisabled::class]);

    $user = User::factory()->create();

    app(SuspendUser::class)->handle($user);
    app(ActivateUser::class)->handle($user);
    app(DisableUser::class)->handle($user);

    Event::assertDispatched(UserSuspended::class);
    Event::assertDispatched(UserActivated::class);
    Event::assertDispatched(UserDisabled::class);
});

test('a state change that changes nothing is not announced', function () {
    Event::fake([UserActivated::class]);

    app(ActivateUser::class)->handle(User::factory()->create());

    Event::assertNotDispatched(UserActivated::class);
});
