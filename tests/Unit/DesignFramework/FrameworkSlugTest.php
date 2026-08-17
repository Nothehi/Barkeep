<?php

use Modules\DesignFramework\Domain\Exceptions\InvalidFrameworkSlug;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkSlug;

it('accepts a well formed address', function (string $value) {
    expect(FrameworkSlug::fromString($value)->value)->toBe($value);
})->with(['bgdf', 'board-game-design', 'design-framework-2']);

it('folds case and trims, because those do not change meaning', function () {
    expect(FrameworkSlug::fromString('  Board-Game-Design  ')->value)->toBe('board-game-design');
});

/**
 * Normalisation is lossless-or-reject. Anything that would change an address's meaning is refused rather
 * than silently rewritten, because an author who typed a specific address is publishing a URL.
 */
it('refuses anything it would have to rewrite', function (string $value) {
    expect(fn () => FrameworkSlug::fromString($value))->toThrow(InvalidFrameworkSlug::class);
})->with([
    'board game design',
    'board_game_design',
    '-leading',
    'trailing-',
    'double--dash',
    'Ünicode',
    '',
    'x',
]);

it('refuses an address the framework routes need for themselves', function (string $value) {
    expect(fn () => FrameworkSlug::fromString($value))->toThrow(InvalidFrameworkSlug::class);
})->with(['versions', 'publish', 'archive', 'create', 'new', 'settings']);

it('reports a bad address against the field the form submitted', function () {
    $refusal = null;

    try {
        FrameworkSlug::fromString('not a slug');
    } catch (InvalidFrameworkSlug $caught) {
        $refusal = $caught;
    }

    expect($refusal?->field())->toBe('slug');
});

/**
 * Deriving from a name is allowed to rewrite its input, because the caller asked for a suggestion rather
 * than for a specific address.
 */
it('derives an address from a name', function () {
    expect(FrameworkSlug::fromName('Board Game Design Framework!')->value)
        ->toBe('board-game-design-framework');
});

it('escapes a reserved word when deriving rather than failing', function () {
    expect(FrameworkSlug::fromName('Versions')->value)->toBe('versions-framework');
});

it('refuses a name with nothing sluggable in it', function () {
    expect(fn () => FrameworkSlug::fromName('!!!'))->toThrow(InvalidFrameworkSlug::class);
});

it('answers whether a string would be valid without raising', function () {
    expect(FrameworkSlug::isValid('board-game-design'))->toBeTrue()
        ->and(FrameworkSlug::isValid('versions'))->toBeFalse();
});

/**
 * The base is shortened to make room rather than the result being allowed to run past the limit, so a
 * long name still produces a usable address on its hundredth collision.
 */
it('keeps a suffixed address inside the length limit', function () {
    $long = FrameworkSlug::fromString(str_repeat('a', FrameworkSlug::MAX_LENGTH));

    expect(mb_strlen($long->withSuffix(100)->value))->toBeLessThanOrEqual(FrameworkSlug::MAX_LENGTH)
        ->and($long->withSuffix(100)->value)->toEndWith('-100');
});
