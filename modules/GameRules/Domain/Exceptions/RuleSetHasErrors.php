<?php

namespace Modules\GameRules\Domain\Exceptions;

/**
 * Raised when a rule set with structural errors is asked to go into play.
 *
 * The one place a validation finding stops something, and the exception to the
 * module's own rule that findings never refuse a save.
 *
 * The distinction is between writing and claiming. A draft full of problems is
 * the normal state of a rule system halfway through being written, and every
 * warning and error in it is saved without complaint. Activating is different:
 * it says "these are the rules now", and that claim is false while a rule is its
 * own ancestor or a transition points into somebody else's rule set. Warnings do
 * not block — a game with no victory condition may be exactly what the designer
 * meant.
 */
final class RuleSetHasErrors extends RuleSystemViolation
{
    /**
     * @param  list<string>  $problems  the first few error messages, for context
     */
    private function __construct(public readonly int $errorCount, public readonly array $problems, string $message)
    {
        parent::__construct($message);
    }

    /**
     * @param  list<string>  $problems
     */
    public static function withCount(int $errorCount, array $problems): self
    {
        return new self($errorCount, $problems, trans_choice(
            '{1}This rule set cannot go into play: one problem has to be fixed first.|[2,*]This rule set cannot go into play: :count problems have to be fixed first.',
            $errorCount,
            ['count' => $errorCount],
        ));
    }

    public function status(): int
    {
        return 409;
    }
}
