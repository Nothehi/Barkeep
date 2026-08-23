<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a rule set is put away for good.
 *
 * Terminal. An archived rule set stays readable forever — that is the whole
 * point of archiving rather than deleting — but nothing about it can change
 * again, so a consumer may treat what it holds as final.
 */
final readonly class RuleSetArchived
{
    public function __construct(
        public string $ruleSetId,
        public string $gameVersionId,
        public string $archivedBy,
    ) {}
}
