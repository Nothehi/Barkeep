<?php

namespace Modules\DesignFramework\Application\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\DesignFramework\Domain\Exceptions\InvalidFrameworkSlug;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkSlug;

/**
 * Checks that a supplied framework address is one the domain would accept.
 *
 * A rule object rather than a regular expression in a rules array, so the definition of a
 * valid address lives in {@see FrameworkSlug} and nowhere else. The value object already
 * knows about length, shape, case folding and reserved words; restating any of that in a
 * `regex:` rule would be a second answer waiting to disagree.
 *
 * Reports the domain's own message, so somebody who typed a reserved word is told which one
 * rather than being shown a pattern.
 */
final class IsAFrameworkSlug implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('That is not a valid framework address.'))->translate();

            return;
        }

        try {
            FrameworkSlug::fromString($value);
        } catch (InvalidFrameworkSlug $invalid) {
            $fail($invalid->getMessage())->translate();
        }
    }
}
