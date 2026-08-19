<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

/**
 * Raised when a prototype version that has already been used is edited.
 *
 * The immutability rule, and the one most likely to look like an
 * inconvenience. Once an iteration, a playtest or an experiment points at a
 * prototype version, that version has become the answer to "what was actually
 * on the table" — and editing it afterwards changes what every record pointing
 * at it says happened. Renaming v3's contents two months later would silently
 * rewrite three iterations' worth of reasoning.
 *
 * The message names the way out, because a caller hitting this wants to change
 * the prototype and there is nothing wrong with that: cut v4. It costs nothing,
 * it is how designers already talk, and it leaves v3 saying what it always said.
 *
 * Note what stays editable: the version's own `name` and `description` are
 * frozen along with everything else, because they are how somebody identifies
 * which state they are looking at. A typo in a description is a smaller loss
 * than a history that can be quietly reworded.
 */
final class PrototypeVersionIsInUse extends IterationRuleViolation
{
    private function __construct(
        public readonly string $prototypeVersionId,
        public readonly int $usageCount,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forVersion(string $prototypeVersionId, int $usageCount): self
    {
        return new self(
            $prototypeVersionId,
            $usageCount,
            __('This version has already been used, so it is part of the design record. Create the next version instead.'),
        );
    }

    public function status(): int
    {
        return 409;
    }
}
