<?php

namespace Modules\DesignFramework\Application\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;

/**
 * Checks that a phase id names one of this version's stages.
 *
 * A phase arrives in a request body rather than through a route binding, which makes it the
 * one identifier in the builder that is not already scoped by the URL. Resolving it *through*
 * the version — rather than looking it up and comparing — means a phase from another version
 * is simply not found.
 *
 * `ContentWriter` performs the same resolution again and raises a domain violation if it
 * fails. That is not redundancy: this rule exists so the form shows the problem next to the
 * phase picker, and the command's check exists so a caller who never went through a form is
 * refused too.
 */
final class PhaseBelongsToVersion implements ValidationRule
{
    public function __construct(private readonly FrameworkVersion $version) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $phase = app(FrameworkRepository::class)->findPhaseById($this->version, $value);

        if ($phase === null) {
            $fail(__('That phase does not belong to this framework version.'))->translate();
        }
    }
}
