<?php

namespace Modules\PrototypeIteration\Application\DTOs;

use Carbon\CarbonImmutable;

/**
 * The handful of conversions every DTO in this module performs.
 *
 * The same three rules Playtesting applies, restated here rather than shared,
 * because a helper imported across a context boundary is a dependency between
 * the two for the sake of forty lines. Getting them right once per module
 * matters more than the code they save:
 *
 * - an empty string is not a value. A form that submits a blank textarea means
 *   "there is no hypothesis", and storing "" would make the difference between
 *   "not stated" and "stated as nothing" invisible everywhere downstream. That
 *   distinction carries real weight here: an experiment with no expected result
 *   is exploratory, and one with an empty expected result is a bug.
 * - timestamps become immutable Carbon instances at the boundary, so nothing
 *   below this layer has to think about what shape a date arrived in.
 * - identifiers that were left blank stay null rather than becoming "", so a
 *   citation with no reference is distinguishable from one pointing at nothing.
 */
final class IterationInput
{
    /**
     * Read an optional block of text, treating blank as absent.
     *
     * @param  array<string, mixed>  $input
     */
    public static function text(array $input, string $key): ?string
    {
        if (! isset($input[$key])) {
            return null;
        }

        $value = trim((string) $input[$key]);

        return $value === '' ? null : $value;
    }

    /**
     * Read a required block of text.
     *
     * @param  array<string, mixed>  $input
     */
    public static function requiredText(array $input, string $key): string
    {
        return trim((string) ($input[$key] ?? ''));
    }

    /**
     * Read an optional timestamp.
     *
     * @param  array<string, mixed>  $input
     */
    public static function timestamp(array $input, string $key): ?CarbonImmutable
    {
        if (! isset($input[$key]) || $input[$key] === '') {
            return null;
        }

        return CarbonImmutable::parse((string) $input[$key]);
    }

    /**
     * Read an optional identifier.
     *
     * @param  array<string, mixed>  $input
     */
    public static function identifier(array $input, string $key): ?string
    {
        if (! isset($input[$key]) || $input[$key] === '') {
            return null;
        }

        return (string) $input[$key];
    }
}
