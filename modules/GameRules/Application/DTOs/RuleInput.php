<?php

namespace Modules\GameRules\Application\DTOs;

/**
 * The small readers every DTO in this module builds itself from.
 *
 * Form requests hand over `validated()`, which is an array of whatever the
 * client sent: a field may be absent, present and null, or present as an empty
 * string that a browser produced from an untouched input. Turning all three into
 * one answer in one place is what keeps twenty-odd `fromArray()` methods from
 * each having a slightly different idea of what "empty" means.
 *
 * {@see has()} is the distinction every update DTO rests on. A field that was not
 * sent keeps its stored value; a field sent as null clears it. Collapsing the two
 * would make a partial update erase everything it did not mention — which, on a
 * rule with a description somebody spent an afternoon on, is the worst bug this
 * module could have.
 */
final readonly class RuleInput
{
    /**
     * Read optional prose.
     *
     * Whitespace-only input becomes null rather than an empty string, so a
     * description somebody typed a space into is absent rather than blank.
     *
     * @param  array<string, mixed>  $input
     */
    public static function text(array $input, string $key): ?string
    {
        $value = $input[$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Read prose that has to be there.
     *
     * @param  array<string, mixed>  $input
     */
    public static function requiredText(array $input, string $key): string
    {
        return self::text($input, $key) ?? '';
    }

    /**
     * Read an identifier, an enum value or an economy handle.
     *
     * Separate from {@see text()} because these are never shown to anybody — they
     * are matched against a `tryFrom`, a primary key or a slug, and a caller
     * reading one wants to know whether it is present rather than whether it is
     * prose.
     *
     * @param  array<string, mixed>  $input
     */
    public static function identifier(array $input, string $key): ?string
    {
        $value = $input[$key] ?? null;

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Read an enum case, falling back to a default.
     *
     * @template TEnum of \BackedEnum
     *
     * @param  array<string, mixed>  $input
     * @param  class-string<TEnum>  $enum
     * @param  TEnum  $default
     * @return TEnum
     */
    public static function enum(array $input, string $key, string $enum, \BackedEnum $default): \BackedEnum
    {
        $value = self::identifier($input, $key);

        if ($value === null) {
            return $default;
        }

        return $enum::tryFrom($value) ?? $default;
    }

    /**
     * Read an enum case that may legitimately be absent.
     *
     * @template TEnum of \BackedEnum
     *
     * @param  array<string, mixed>  $input
     * @param  class-string<TEnum>  $enum
     * @return TEnum|null
     */
    public static function optionalEnum(array $input, string $key, string $enum): ?\BackedEnum
    {
        $value = self::identifier($input, $key);

        return $value === null ? null : $enum::tryFrom($value);
    }

    /**
     * Read a whole number, falling back to the given default.
     *
     * @param  array<string, mixed>  $input
     */
    public static function integer(array $input, string $key, int $default = 0): int
    {
        $value = $input[$key] ?? null;

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Read a flag, falling back to the given default.
     *
     * The default matters: an unchecked checkbox is simply absent from a form
     * submission, so "not sent" has to mean whatever the field's own default is
     * rather than always meaning false.
     *
     * @param  array<string, mixed>  $input
     */
    public static function flag(array $input, string $key, bool $default = false): bool
    {
        if (! array_key_exists($key, $input) || $input[$key] === null) {
            return $default;
        }

        return filter_var($input[$key], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Determine whether a field was sent at all.
     *
     * @param  array<string, mixed>  $input
     */
    public static function has(array $input, string $key): bool
    {
        return array_key_exists($key, $input);
    }
}
