<?php

namespace Modules\Workspace\Application\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\Workspace\Domain\ValueObjects\WorkspaceSlug;

/**
 * Validates a workspace address at the HTTP boundary.
 *
 * The check delegates to the value object rather than restating its pattern,
 * so the form and the domain can never disagree about what a valid address
 * is. Whichever of the two rules is relaxed first, the other follows.
 */
final class ValidWorkspaceSlug implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (! WorkspaceSlug::isValid($value)) {
            $fail(__('The workspace address may only contain lowercase letters, numbers and hyphens, and may not be a reserved word.'));
        }
    }
}
