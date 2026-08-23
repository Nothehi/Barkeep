<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\TriggerData;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\RuleTriggerUpdated;
use Modules\GameRules\Domain\Exceptions\RuleNameIsTaken;
use Modules\GameRules\Domain\Models\RuleTrigger;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Rename a trigger or change when it fires.
 */
final class UpdateRuleTrigger
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleTrigger $trigger, TriggerData $data): RuleTrigger
    {
        $ruleSet = $trigger->ruleSet;

        if ($ruleSet === null) {
            return $trigger;
        }

        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        if ($data->name !== null && $data->name !== $trigger->name) {
            if ($this->structure->ruleSetHasTriggerNamed($ruleSet, $data->name, $trigger->getKey())) {
                throw RuleNameIsTaken::forTrigger($data->name);
            }

            $trigger->name = $data->name;
        }

        if ($data->sent('description')) {
            $trigger->description = $data->description;
        }

        if ($data->triggerType !== null) {
            $trigger->trigger_type = $data->triggerType;
        }

        if ($data->position !== null) {
            $trigger->position = $data->position;
        }

        $changed = array_keys($trigger->getDirty());

        $trigger->save();

        if ($changed !== []) {
            event(new RuleTriggerUpdated(
                triggerId: $trigger->getKey(),
                ruleSetId: $trigger->rule_set_id,
                changedFields: $changed,
            ));
        }

        return $trigger;
    }
}
