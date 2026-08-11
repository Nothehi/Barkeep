<?php

use Modules\Identity\Domain\Enums\UserStatus;

test('only an active account may authenticate', function () {
    expect(UserStatus::Active->canAuthenticate())->toBeTrue()
        ->and(UserStatus::Suspended->canAuthenticate())->toBeFalse()
        ->and(UserStatus::Disabled->canAuthenticate())->toBeFalse();
});

test('every status is backed by a stable persisted value', function () {
    expect(array_map(fn (UserStatus $status) => $status->value, UserStatus::cases()))
        ->toBe(['active', 'suspended', 'disabled']);
});

test('every status explains why access was denied', function (UserStatus $status) {
    expect($status->deniedReason())->not->toBe('')
        ->and($status->label())->not->toBe('');
})->with(UserStatus::cases());
