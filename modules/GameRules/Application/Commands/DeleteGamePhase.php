<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\GamePhaseDeleted;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\Identity\Domain\Models\User;

/**
 * Remove a stage of play.
 *
 * Its transitions go with it — an edge with one end missing is not an edge with a
 * gap in it, it is nothing.
 *
 * The rules and actions that happened during it survive with their phase cleared,
 * and the validator then reports each action as having no phase. That is the
 * useful outcome: an action nobody can place in the turn is worth noticing, and
 * silently deleting somebody's Build action because the phase around it went away
 * would not be.
 *
 * Child phases are promoted rather than deleted, for the same reason a rule's
 * children are.
 */
final class DeleteGamePhase
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, GamePhase $phase): void
    {
        $ruleSet = $phase->ruleSet;

        if ($ruleSet !== null) {
            $this->guard->ensureRuleSetAcceptsChanges($ruleSet);
        }

        $phaseId = $phase->getKey();
        $ruleSetId = $phase->rule_set_id;
        $slug = $phase->slug;

        $phase->delete();

        event(new GamePhaseDeleted(
            phaseId: $phaseId,
            ruleSetId: $ruleSetId,
            slug: $slug,
        ));
    }
}
