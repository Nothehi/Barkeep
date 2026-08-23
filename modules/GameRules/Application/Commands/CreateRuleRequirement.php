<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Commands\Concerns\ResolvesRecordOwner;
use Modules\GameRules\Application\DTOs\RequirementData;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Enums\RequirementType;
use Modules\GameRules\Domain\Models\RuleRequirement;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Say what has to be true before a rule or an action applies.
 *
 * Prose with a category, never an expression — section 17 of the brief refuses a
 * scripting language, and the description column is where the rule actually lives.
 * A requirement of type `resource` may additionally point at one of the game's
 * resources by handle, which is how "you hold at least five wood" gets the *five*
 * and the *wood* from the economy rather than from a copy here.
 *
 * Exactly one owner, resolved by {@see ResolvesRecordOwner}. No event: a
 * requirement is a detail *of* the thing it gates, and a consumer that cares
 * about an action's requirements is already listening for the action.
 */
final class CreateRuleRequirement
{
    use ResolvesRecordOwner;

    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleSet $ruleSet, RequirementData $data): RuleRequirement
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        [$ruleId, $actionId] = $this->resolveOwner(
            $this->catalogue,
            $ruleSet,
            $data->ruleId,
            $data->actionId,
            forEffect: false,
        );

        $requirement = new RuleRequirement;

        $requirement->fill([
            'description' => $data->description ?? '',
            'value' => $data->value,
        ]);

        $requirement->rule_set_id = $ruleSet->getKey();
        $requirement->rule_id = $ruleId;
        $requirement->action_id = $actionId;
        $requirement->requirement_type = $data->requirementType ?? RequirementType::default();
        $requirement->economy_resource_slug = $data->economyResourceSlug;
        $requirement->position = $data->position
            ?? $this->structure->countRequirementsFor($ruleSet, $ruleId, $actionId);

        $requirement->save();

        return $requirement;
    }
}
