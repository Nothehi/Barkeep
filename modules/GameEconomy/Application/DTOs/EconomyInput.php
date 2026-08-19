<?php

namespace Modules\GameEconomy\Application\DTOs;

use Modules\GameEconomy\Domain\ValueObjects\Quantity;

/**
 * The small readers every DTO in this module builds itself from.
 *
 * Form requests hand over `validated()`, which is an array of whatever the
 * client sent: a field may be absent, present and null, or present as an empty
 * string that a browser produced from an untouched input. Turning all three into
 * one answer in one place is what keeps thirty-odd `fromArray()` methods from
 * each having a slightly different idea of what "empty" means.
 *
 * The amount readers are the ones that matter most. Every number in this module
 * is exact, and the only way that stays true is if there is exactly one boundary
 * where a request value becomes a {@see Quantity} — here.
 */
final readonly class EconomyInput
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
     * Read an identifier or an enum value.
     *
     * Separate from {@see text()} because these are never trimmed for display
     * and never shown to anybody — they are matched against a `tryFrom` or a
     * primary key, and a caller reading one wants to know whether it is present
     * rather than whether it is prose.
     *
     * @param  array<string, mixed>  $input
     */
    public static function identifier(array $input, string $key): ?string
    {
        $value = $input[$key] ?? null;

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        }

        return null;
    }

    /**
     * Read an optional amount.
     *
     * Anything that is not a number becomes null, which is the same answer as an
     * absent field. Validation has already refused genuinely bad input by the
     * time this runs, so the fallback is about empty strings rather than about
     * catching mistakes.
     *
     * @param  array<string, mixed>  $input
     */
    public static function amount(array $input, string $key): ?Quantity
    {
        $value = $input[$key] ?? null;

        if ($value === null || is_bool($value) || is_array($value)) {
            return null;
        }

        return Quantity::isValid($value) ? Quantity::from($value) : null;
    }

    /**
     * Read an amount, falling back to zero.
     *
     * Used where the column is not nullable — a flow's amount, a cost's amount,
     * a variable's value. Zero is a meaningful configuration in all three, and
     * the analysis reports it rather than the request refusing it.
     *
     * @param  array<string, mixed>  $input
     */
    public static function requiredAmount(array $input, string $key): Quantity
    {
        return self::amount($input, $key) ?? Quantity::zero();
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
     * Determine whether a field was sent at all.
     *
     * The distinction every update DTO rests on: a field that was not sent keeps
     * its stored value, and a field sent as null clears it. Collapsing the two
     * would make a partial update erase everything it did not mention.
     *
     * @param  array<string, mixed>  $input
     */
    public static function has(array $input, string $key): bool
    {
        return array_key_exists($key, $input);
    }
}
