<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a rule set becomes the one in play.
 *
 * "These are the rules now." A design state has exactly one active rule set, so
 * activating one retires whichever was active before — and that predecessor is
 * named here rather than left to a consumer to work out, because the pair is the
 * interesting fact. A studio's rule history is a chain of these.
 *
 * Activation is refused while the validator reports errors, so a consumer may
 * assume the set it is told about is at least structurally coherent.
 */
final readonly class RuleSetActivated
{
    public function __construct(
        public string $ruleSetId,
        public string $gameVersionId,
        public ?string $supersededRuleSetId,
        public string $activatedBy,
    ) {}
}
