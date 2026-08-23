<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a rule set names a mechanism it uses.

 * Note the noun. This is a mechanic *in one game's rule system*, not an entry in
 * GameDesign's shared vocabulary of design terms — see `RuleMechanic` for why
 * the two are different things with similar names.
 */
final readonly class RuleMechanicCreated
{
    public function __construct(
        public string $mechanicId,
        public string $ruleSetId,
        public string $slug,
    ) {}
}
