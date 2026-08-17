<?php

use Modules\DesignFramework\Domain\Exceptions\InvalidContentSlug;
use Modules\DesignFramework\Domain\ValueObjects\ContentSlug;

it('accepts a well formed address', function () {
    expect(ContentSlug::fromString('core-loop')->value)->toBe('core-loop');
});

it('refuses anything it would have to rewrite', function (string $value) {
    expect(fn () => ContentSlug::fromString($value))->toThrow(InvalidContentSlug::class);
})->with(['core loop', 'core_loop', '-core', 'core-', '', 'x']);

it('refuses an address the builder routes need for themselves', function (string $value) {
    expect(fn () => ContentSlug::fromString($value))->toThrow(InvalidContentSlug::class);
})->with(['phases', 'criteria', 'practices', 'principles', 'prompts', 'checklists', 'reorder', 'progress']);

/**
 * Content titles here are often whole sentences — "Does the game provide meaningful decisions?" — so the
 * derived address is truncated at a word boundary rather than mid-word.
 */
it('derives a readable address from a sentence-length title', function () {
    $slug = ContentSlug::fromTitle('Does the game provide meaningful decisions?');

    expect($slug->value)->toBe('does-the-game-provide-meaningful-decisions');
});

it('escapes a reserved word when deriving rather than failing', function () {
    expect(ContentSlug::fromTitle('Criteria')->value)->toBe('criteria-item');
});

it('refuses a title with nothing sluggable in it', function () {
    expect(fn () => ContentSlug::fromTitle('?!'))->toThrow(InvalidContentSlug::class);
});

it('suffixes a colliding address', function () {
    expect(ContentSlug::fromTitle('Core loop')->withSuffix(2)->value)->toBe('core-loop-2');
});
