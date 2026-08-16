<?php

use Modules\GameDesign\Domain\Exceptions\InvalidGameSlug;
use Modules\GameDesign\Domain\ValueObjects\GameSlug;

it('accepts a well formed address', function (string $value) {
    expect(GameSlug::fromString($value)->value)->toBe($value);
})->with([
    'a word' => 'catan',
    'hyphenated' => 'bears-and-bridges',
    'with digits' => 'seven-wonders-2',
    'shortest allowed' => 'go',
]);

/**
 * Case folding and trimming cannot change which game an address means, so
 * they are normalised. Everything else is refused rather than rewritten.
 */
it('folds case and trims whitespace', function () {
    expect(GameSlug::fromString('  Bears-And-Bridges  ')->value)->toBe('bears-and-bridges');
});

it('refuses anything that would have to be rewritten', function (string $value) {
    expect(fn () => GameSlug::fromString($value))->toThrow(InvalidGameSlug::class);
})->with([
    'empty' => '',
    'blank' => '   ',
    'spaces' => 'bears and bridges',
    'underscores' => 'bears_and_bridges',
    'accents' => 'béars',
    'leading hyphen' => '-bears',
    'trailing hyphen' => 'bears-',
    'doubled hyphen' => 'bears--bridges',
    'a slash' => 'bears/bridges',
    'one character' => 'b',
]);

/**
 * A game called "Settings" must not become indistinguishable from the screen
 * that configures one.
 */
it('refuses the words the game routes need for themselves', function (string $value) {
    expect(fn () => GameSlug::fromString($value))->toThrow(InvalidGameSlug::class);
})->with(['settings', 'versions', 'version', 'archive', 'status', 'create', 'new', 'edit', 'design-phase']);

it('refuses an address longer than the column allows', function () {
    expect(fn () => GameSlug::fromString(str_repeat('a', GameSlug::MAX_LENGTH + 1)))
        ->toThrow(InvalidGameSlug::class);
});

/**
 * Deriving is allowed to rewrite, because the caller asked for a suggestion
 * rather than for a specific address.
 */
it('derives an address from a name', function (string $name, string $expected) {
    expect(GameSlug::fromName($name)->value)->toBe($expected);
})->with([
    'ampersand' => ['Bears & Bridges', 'bears-bridges'],
    'punctuation' => ['Seven Wonders: Duel', 'seven-wonders-duel'],
    'accents' => ['Café Royale', 'cafe-royale'],
    'already a slug' => ['bears-and-bridges', 'bears-and-bridges'],
]);

it('steps a derived address around a reserved word', function () {
    expect(GameSlug::fromName('Settings')->value)->toBe('settings-game');
});

it('refuses to derive an address from a name with nothing sluggable in it', function () {
    expect(fn () => GameSlug::fromName('!!!'))->toThrow(InvalidGameSlug::class);
});

it('keeps a suffixed address within the length limit', function () {
    $long = GameSlug::fromString(str_repeat('a', GameSlug::MAX_LENGTH));

    expect(mb_strlen($long->withSuffix(100)->value))->toBeLessThanOrEqual(GameSlug::MAX_LENGTH);
});

it('reports validity without throwing', function () {
    expect(GameSlug::isValid('bears-and-bridges'))->toBeTrue()
        ->and(GameSlug::isValid('bears and bridges'))->toBeFalse()
        ->and(GameSlug::isValid('settings'))->toBeFalse();
});

it('compares two addresses by value', function () {
    expect(GameSlug::fromString('catan')->equals(GameSlug::fromString('CATAN')))->toBeTrue()
        ->and(GameSlug::fromString('catan')->equals(GameSlug::fromString('carcassonne')))->toBeFalse()
        ->and((string) GameSlug::fromString('catan'))->toBe('catan');
});
