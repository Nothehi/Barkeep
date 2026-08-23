<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\RequirementData;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Models\RuleRequirement;
use Modules\Identity\Domain\Models\User;

/**
 * Reword a requirement, change its threshold, or re-price it.
 *
 * The owner is not editable. Moving a requirement from one action to another is
 * two operations — delete it here, write it there — and offering it as one would
 * mean a `rule_id` in a PATCH body could silently relocate a gate from the action
 * a designer is looking at to one they are not.
 */
final class UpdateRuleRequirement
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, RuleRequirement $requirement, RequirementData $data): RuleRequirement
    {
        $ruleSet = $requirement->ruleSet;

        if ($ruleSet === null) {
            return $requirement;
        }

        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        if ($data->description !== null) {
            $requirement->description = $data->description;
        }

        if ($data->sent('value')) {
            $requirement->value = $data->value;
        }

        if ($data->requirementType !== null) {
            $requirement->requirement_type = $data->requirementType;
        }

        if ($data->sent('economy_resource_slug')) {
            $requirement->economy_resource_slug = $data->economyResourceSlug;
        }

        if ($data->position !== null) {
            $requirement->position = $data->position;
        }

        $requirement->save();

        return $requirement;
    }
}
