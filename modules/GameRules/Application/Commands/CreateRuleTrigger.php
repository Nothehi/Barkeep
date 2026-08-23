<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\TriggerData;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Enums\TriggerType;
use Modules\GameRules\Domain\Events\RuleTriggerCreated;
use Modules\GameRules\Domain\Exceptions\RuleNameIsTaken;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\RuleTrigger;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Name something that happens automatically.
 *
 * Recorded, never fired. This command writes four columns and dispatches an
 * event; nothing anywhere in the module reads a trigger and acts on it, and
 * section 23 of the brief is explicit that the execution engine belongs to a
 * bounded context that does not exist yet.
 */
final class CreateRuleTrigger
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleSet $ruleSet, TriggerData $data): RuleTrigger
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $name = $data->name ?? '';

        if ($this->structure->ruleSetHasTriggerNamed($ruleSet, $name)) {
            throw RuleNameIsTaken::forTrigger($name);
        }

        $trigger = new RuleTrigger;

        $trigger->fill([
            'name' => $name,
            'description' => $data->description,
        ]);

        $trigger->rule_set_id = $ruleSet->getKey();
        $trigger->trigger_type = $data->triggerType ?? TriggerType::default();
        $trigger->position = $data->position ?? $this->structure->triggersOf($ruleSet)->count();

        $trigger->save();

        $trigger->setRelation('ruleSet', $ruleSet);

        event(new RuleTriggerCreated(
            triggerId: $trigger->getKey(),
            ruleSetId: $ruleSet->getKey(),
            name: $trigger->name,
        ));

        return $trigger;
    }
}
