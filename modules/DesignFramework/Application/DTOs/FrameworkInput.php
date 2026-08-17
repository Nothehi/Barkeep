<?php

namespace Modules\DesignFramework\Application\DTOs;

/**
 * The handful of conversions every design framework DTO performs.
 *
 * The same three rules the other contexts apply at their boundaries, applied
 * identically here because getting them right once matters more than the code
 * they save:
 *
 * - an empty string is not a value. A form that submits a blank textarea means
 *   "there is no description", and storing "" would make the difference between
 *   "not written" and "written as nothing" invisible everywhere downstream.
 * - required text is trimmed but kept, so a title of spaces fails validation
 *   rather than being stored as one.
 * - booleans arrive from checkboxes as "1", "true", "on" or absent, and are
 *   normalised once here rather than at each call site.
 */
final class FrameworkInput
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
     * Read a flag, defaulting when it was not sent.
     *
     * A missing checkbox is a real "false" in HTML, so the default matters: a
     * checklist item's `required` defaults to true when the field is absent
     * because the field is only absent on the API, while the form always sends it.
     *
     * @param  array<string, mixed>  $input
     */
    public static function flag(array $input, string $key, bool $default): bool
    {
        if (! array_key_exists($key, $input) || $input[$key] === null || $input[$key] === '') {
            return $default;
        }

        return filter_var($input[$key], FILTER_VALIDATE_BOOL);
    }
}
