<?php

use Modules\GameRules\Domain\Exceptions\InvalidRuleSlug;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;

it('derives a handle from whatever the designer typed', function (string $name, string $expected) {
    expect(RuleSlug::fromName($name)->value)->toBe($expected);
})->with([
    ['Combat', 'combat'],
    ['Line of sight', 'line_of_sight'],
    ['  Round Start  ', 'round_start'],
    ['Draw a card!', 'draw_a_card'],
    ['Phase 2', 'phase_2'],
]);

it('generates a handle when the name slugs to nothing at all', function () {
    /*
     * A phase called "???" is somebody's placeholder, not a mistake. Refusing it
     * would stop them getting the thought down, so a handle is invented instead.
     */
    $slug = RuleSlug::fromName('???');

    expect($slug->value)->toStartWith('rule_')
        ->and(RuleSlug::isValid($slug->value))->toBeTrue();
});

it('transliterates a Persian name rather than inventing a handle', function () {
    /*
     * These handles never appear in a URL, so an unreadable-but-stable
     * transliteration is the right outcome — unlike GameDesign's slugs, which are
     * URL segments and are seeded explicitly in Latin.
     */
    expect(RuleSlug::fromName('برپایی')->value)->toBe('brpayy');
});

it('accepts a handle that is already one', function () {
    expect(RuleSlug::fromString('action_phase')->value)->toBe('action_phase')
        ->and(RuleSlug::fromString('  ACTION_PHASE ')->value)->toBe('action_phase');
});

it('refuses a string that is not shaped like a handle', function (string $value) {
    expect(fn () => RuleSlug::fromString($value))->toThrow(InvalidRuleSlug::class);
})->with([
    'hyphenated' => ['action-phase'],
    'trailing underscore' => ['action_'],
    'double underscore' => ['action__phase'],
    'empty' => [''],
    'punctuation' => ['action!'],
]);

it('truncates a very long name', function () {
    $slug = RuleSlug::fromName(str_repeat('phase ', 40));

    expect(mb_strlen($slug->value))->toBeLessThanOrEqual(RuleSlug::MAX_LENGTH);
});

it('compares by value', function () {
    expect(RuleSlug::fromName('Combat')->equals(RuleSlug::fromName('combat')))->toBeTrue()
        ->and((string) RuleSlug::fromName('Combat'))->toBe('combat');
});
