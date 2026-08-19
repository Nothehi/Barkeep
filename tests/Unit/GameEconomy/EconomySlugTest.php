<?php

use Modules\GameEconomy\Domain\Exceptions\InvalidEconomySlug;
use Modules\GameEconomy\Domain\ValueObjects\EconomySlug;

it('derives a handle from what the designer typed', function () {
    expect(EconomySlug::fromName('Action Points')->value)->toBe('action_points')
        ->and(EconomySlug::fromName('Victory Points')->value)->toBe('victory_points')
        ->and(EconomySlug::fromName('Wood')->value)->toBe('wood');
});

it('uses underscores rather than hyphens, because these read beside a spreadsheet column', function () {
    expect(EconomySlug::fromName('Wood harvest amount')->value)->toBe('wood_harvest_amount');
});

/**
 * Persian is a supported locale of this application, so a name written in a
 * script `Str::slug` cannot transliterate is a real case rather than a
 * theoretical one — and refusing it would mean a designer could not call a
 * resource what they call it.
 */
it('still produces a handle when a name slugs to nothing', function () {
    $slug = EconomySlug::fromName('•••');

    expect($slug->value)->toStartWith('item_')
        ->and(EconomySlug::isValid($slug->value))->toBeTrue();
});

it('refuses a string that is not shaped like a handle', function () {
    expect(fn () => EconomySlug::fromString('Action Points'))->toThrow(InvalidEconomySlug::class);
});

it('accepts a handle that already is one', function () {
    expect(EconomySlug::fromString('action_points')->value)->toBe('action_points');
});
