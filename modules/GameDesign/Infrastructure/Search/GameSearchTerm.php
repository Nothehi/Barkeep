<?php

namespace Modules\GameDesign\Infrastructure\Search;

use Illuminate\Support\Str;
use Stringable;

/**
 * A search box's contents, turned into something safe to hand a `LIKE`.
 *
 * Searching games is a substring match over their name and description today.
 * That is honestly all the games screen needs, and reaching for a search
 * engine before anybody has more than a screenful of projects would be
 * infrastructure in search of a problem.
 *
 * What it is not allowed to be is naive. A term containing `%` or `_` is a
 * wildcard to `LIKE`, so typing `%` would quietly match every game and typing
 * `50%` would match nothing anybody expected. Escaping happens here, once,
 * rather than at each query that searches.
 *
 * When this does become a real search index, this class is the seam: callers
 * ask for a pattern, and what produces it can change underneath them.
 */
final readonly class GameSearchTerm implements Stringable
{
    /**
     * The character `LIKE ... ESCAPE` is told to treat as the escape.
     *
     * A backslash is the default on MySQL but not on PostgreSQL, so queries
     * built from this pattern state it explicitly.
     */
    public const ESCAPE = '\\';

    private function __construct(public string $value) {}

    /**
     * Read a search term from user input, or null when there is nothing to
     * search for.
     */
    public static function fromInput(?string $value): ?self
    {
        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return null;
        }

        return new self(Str::limit($trimmed, 120, end: ''));
    }

    /**
     * The `LIKE` pattern that matches this term anywhere in a value.
     */
    public function pattern(): string
    {
        $escaped = str_replace(
            [self::ESCAPE, '%', '_'],
            [self::ESCAPE.self::ESCAPE, self::ESCAPE.'%', self::ESCAPE.'_'],
            Str::lower($this->value),
        );

        return '%'.$escaped.'%';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
