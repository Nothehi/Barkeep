<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\RuleActionData;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\RuleActionUpdated;
use Modules\GameRules\Domain\Exceptions\RuleSlugIsTaken;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Rename an action, move it to another phase, retire it, or wire it to the
 * economy.
 *
 * What it requires and what it does are not editable from here: requirements and
 * effects have their own commands, because each is a separate row.
 */
final class UpdateRuleAction
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleAction $action, RuleActionData $data): RuleAction
    {
        $ruleSet = $action->ruleSet;

        if ($ruleSet === null) {
            return $action;
        }

        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        if ($data->name !== null && $data->name !== $action->name) {
            $slug = RuleSlug::fromName($data->name);

            if ($this->structure->ruleSetHasActionSlug($ruleSet, $slug, $action->getKey())) {
                throw RuleSlugIsTaken::forAction($slug);
            }

            $action->name = $data->name;
            $action->slug = $slug->value;
        }

        if ($data->sent('description')) {
            $action->description = $data->description;
        }

        if ($data->sent('phase_id')) {
            $action->phase_id = $data->phaseId === null
                ? null
                : $this->catalogue->phaseOf($ruleSet, $data->phaseId)->getKey();
        }

        if ($data->actionType !== null) {
            $action->action_type = $data->actionType;
        }

        if ($data->status !== null) {
            $action->status = $data->status;
        }

        if ($data->sent('economy_action_slug')) {
            $action->economy_action_slug = $data->economyActionSlug;
        }

        if ($data->position !== null) {
            $action->position = $data->position;
        }

        $changed = array_keys($action->getDirty());

        $action->save();

        if ($changed !== []) {
            event(new RuleActionUpdated(
                actionId: $action->getKey(),
                ruleSetId: $action->rule_set_id,
                changedFields: $changed,
            ));
        }

        return $action;
    }
}
