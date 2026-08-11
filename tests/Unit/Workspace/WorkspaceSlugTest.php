<?php

use Modules\Workspace\Domain\Exceptions\InvalidWorkspaceSlug;
use Modules\Workspace\Domain\ValueObjects\WorkspaceSlug;

it('accepts a well formed address', function () {
    expect(WorkspaceSlug::fromString('my-board-game-studio')->value)
        ->toBe('my-board-game-studio');
});

it('folds case and trims surrounding whitespace', function () {
    expect(WorkspaceSlug::fromString('  My-Studio  ')->value)->toBe('my-studio');
});

/**
 * Normalising further would change which workspace an address means, so a
 * supplied slug is refused rather than rewritten.
 */
it('refuses to rewrite an address somebody supplied', function (string $value) {
    expect(fn () => WorkspaceSlug::fromString($value))
        ->toThrow(InvalidWorkspaceSlug::class);
})->with([
    'spaces' => 'my studio',
    'underscores' => 'my_studio',
    'accents' => 'stüdio-über',
    'leading hyphen' => '-studio',
    'trailing hyphen' => 'studio-',
    'double hyphen' => 'my--studio',
    'punctuation' => 'studio!',
    'empty' => '',
]);

it('rejects addresses that are too short or too long', function () {
    expect(fn () => WorkspaceSlug::fromString('ab'))
        ->toThrow(InvalidWorkspaceSlug::class)
        ->and(fn () => WorkspaceSlug::fromString(str_repeat('a', WorkspaceSlug::MAX_LENGTH + 1)))
        ->toThrow(InvalidWorkspaceSlug::class);
});

it('rejects addresses the platform keeps for itself', function (string $reserved) {
    expect(fn () => WorkspaceSlug::fromString($reserved))
        ->toThrow(InvalidWorkspaceSlug::class);
})->with(['settings', 'api', 'admin', 'workspaces', 'login']);

/**
 * Deriving from a name is the one case where rewriting is correct: the caller
 * asked for a suggestion rather than for a specific address.
 */
it('derives an address from a workspace name', function () {
    expect(WorkspaceSlug::fromName('My Board Game Studio!')->value)
        ->toBe('my-board-game-studio');
});

it('keeps a derived address clear of the reserved list', function () {
    expect(WorkspaceSlug::fromName('Settings')->value)->toBe('settings-workspace');
});

it('refuses a name with nothing sluggable in it', function () {
    expect(fn () => WorkspaceSlug::fromName('!!!'))
        ->toThrow(InvalidWorkspaceSlug::class);
});

it('numbers collisions deterministically', function () {
    $base = WorkspaceSlug::fromString('studio');

    expect($base->withSuffix(2)->value)->toBe('studio-2')
        ->and($base->withSuffix(2)->value)->toBe('studio-2')
        ->and($base->withSuffix(37)->value)->toBe('studio-37');
});

it('shortens the base rather than exceeding the length limit', function () {
    $long = WorkspaceSlug::fromString(str_repeat('a', WorkspaceSlug::MAX_LENGTH));

    expect(mb_strlen($long->withSuffix(42)->value))
        ->toBeLessThanOrEqual(WorkspaceSlug::MAX_LENGTH)
        ->and($long->withSuffix(42)->value)->toEndWith('-42');
});

it('reports validity without throwing', function () {
    expect(WorkspaceSlug::isValid('my-studio'))->toBeTrue()
        ->and(WorkspaceSlug::isValid('my studio'))->toBeFalse();
});
