<?php

namespace Modules\GameRules\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\Identity\Domain\Models\User;

/**
 * Put a rule set's actions into the order the designer arranged them in.
 *
 * Display order rather than a rule: which action is listed first says nothing
 * about which may be taken first. It still matters — an action list a designer
 * cannot scan is a list they will stop reading — which is why it is theirs to set
 * rather than alphabetical.
 */
final class ReorderRuleActions
{
    public function __construct(
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    /**
     * @param  list<string>  $orderedActionIds
     */
    public function handle(User $actor, RuleSet $ruleSet, array $orderedActionIds): void
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $actions = [];

        foreach ($orderedActionIds as $actionId) {
            $actions[] = $this->catalogue->actionOf($ruleSet, $actionId, 'action_ids');
        }

        DB::transaction(function () use ($actions): void {
            foreach ($actions as $position => $action) {
                $action->position = $position;
                $action->save();
            }
        });
    }
}
