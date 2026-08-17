<?php

namespace Modules\DesignFramework\Infrastructure\Search;

use Illuminate\Support\Str;
use Stringable;

/**
 * A search box's contents, turned into something safe to hand a `LIKE`.
 *
 * Searching frameworks is a substring match over their name and description. There
 * will never be many — a methodology is a substantial thing to write — so reaching
 * for a search engine here would be infrastructure in search of a problem.
 *
 * What it is not allowed to be is naive. A term containing `%` or `_` is a wildcard
 * to `LIKE`, so typing `%` would quietly match everything and typing `50%` would
 * match nothing anybody expected. Escaping happens here, once, rather than at each
 * query that searches.
 */
final readonly class FrameworkSearchTerm implements Stringable
{
    /**
     * The character `LIKE ... ESCAPE` is told to treat as the escape.
     *
     * A backslash is the default on MySQL but not on PostgreSQL, so queries built
     * from this pattern state it explicitly.
     */
    public const ESCAPE = '\\';

    private function __construct(public string $value) {}

    /**
     * Read a search term from user input, or null when there is nothing to search
     * for.
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
