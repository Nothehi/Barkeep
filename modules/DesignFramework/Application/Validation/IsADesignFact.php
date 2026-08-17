<?php

namespace Modules\DesignFramework\Application\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\DesignFramework\Infrastructure\GameDesign\DesignFacts;

/**
 * Refuses a fact key nothing knows how to read.
 *
 * Content that names a fact the module cannot answer would never complete — it
 * would sit on a phase page saying "record the vibes to answer this" and count
 * against the game's progress forever. `DesignFacts` is the list of keys that
 * have a reader, and this is what keeps the two in step.
 *
 * Checked at the edge rather than in the command, because a mistyped key is a
 * typo in a form rather than a domain rule being broken — the author wants it
 * next to the field they typed it into.
 */
final class IsADesignFact implements ValidationRule
{
    public function __construct(private readonly DesignFacts $facts) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): void  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! $this->facts->knows($value)) {
            $fail(__('Nothing in a game\'s design record answers ":value".', [
                'value' => is_string($value) ? $value : gettype($value),
            ]));
        }
    }
}
