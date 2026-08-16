<?php

use Modules\GameDesign\Infrastructure\Search\GameSearchTerm;

it('treats an empty search box as no search at all', function (?string $value) {
    expect(GameSearchTerm::fromInput($value))->toBeNull();
})->with(['null' => null, 'empty' => '', 'whitespace' => "  \t "]);

it('matches a term anywhere in a value', function () {
    expect(GameSearchTerm::fromInput('bears')?->pattern())->toBe('%bears%');
});

it('folds case so the search works on any collation', function () {
    expect(GameSearchTerm::fromInput('BeArS')?->pattern())->toBe('%bears%');
});

/**
 * The reason this class exists. A `%` typed into a search box is a character
 * somebody is looking for, not an instruction to match everything.
 */
it('escapes the characters LIKE would otherwise treat as wildcards', function (string $input, string $expected) {
    expect(GameSearchTerm::fromInput($input)?->pattern())->toBe($expected);
})->with([
    'percent' => ['50%', '%50\\%%'],
    'underscore' => ['a_b', '%a\\_b%'],
    'backslash' => ['a\\b', '%a\\\\b%'],
    'all at once' => ['%_\\', '%\\%\\_\\\\%'],
]);

it('does not let a pasted essay reach the query', function () {
    $term = GameSearchTerm::fromInput(str_repeat('a', 500));

    expect(mb_strlen((string) $term))->toBe(120);
});
