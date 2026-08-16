<?php

namespace Modules\Playtesting\Application\DTOs;

use Carbon\CarbonImmutable;

/**
 * The handful of conversions every playtesting DTO performs.
 *
 * Three rules, applied identically everywhere, because getting them right
 * once matters more than the small amount of code they save:
 *
 * - an empty string is not a value. A form that submits a blank textarea means
 *   "there is no hypothesis", and storing "" would make the difference between
 *   "not stated" and "stated as nothing" invisible everywhere downstream.
 * - timestamps become immutable Carbon instances at the boundary, so nothing
 *   below this layer has to think about what shape a date arrived in.
 * - integers that were left blank stay null rather than becoming zero, which
 *   for a rating is the difference between "did not score it" and "scored it
 *   badly".
 */
final class PlaytestInput
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
     * Read an optional whole number.
     *
     * @param  array<string, mixed>  $input
     */
    public static function integer(array $input, string $key): ?int
    {
        if (! isset($input[$key]) || $input[$key] === '') {
            return null;
        }

        return (int) $input[$key];
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
