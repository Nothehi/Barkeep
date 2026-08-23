<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Enums\RuleSetStatus;
use Modules\GameRules\Domain\Events\RuleSetArchived;
use Modules\GameRules\Domain\Exceptions\InvalidRuleSetTransition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\Identity\Domain\Models\User;

/**
 * Put a rule system away for good.
 *
 * Terminal, and reachable from either draft or active — a rule set somebody
 * started and abandoned is a real outcome and should not have to be put into play
 * first in order to be put away.
 *
 * Archiving does not delete anything. The rules stay readable forever, which is
 * the whole point: a playtest run against them two years ago is only
 * interpretable while the rules it was run against can still be read.
 *
 * There is no way back. A studio returning to an older rule system clones it into
 * a new draft, which is also how they would describe it out loud.
 */
final class ArchiveRuleSet
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, RuleSet $ruleSet): RuleSet
    {
        $this->guard->ensureRuleSetAcceptsLifecycleChange($ruleSet);

        if (! $ruleSet->status->canTransitionTo(RuleSetStatus::Archived)) {
            throw InvalidRuleSetTransition::between($ruleSet->status, RuleSetStatus::Archived);
        }

        $ruleSet->status = RuleSetStatus::Archived;
        $ruleSet->save();

        event(new RuleSetArchived(
            ruleSetId: $ruleSet->getKey(),
            gameVersionId: $ruleSet->game_version_id,
            archivedBy: $actor->getKey(),
        ));

        return $ruleSet;
    }
}
