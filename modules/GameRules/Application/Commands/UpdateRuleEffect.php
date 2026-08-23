<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\EffectData;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\RuleEffectUpdated;
use Modules\GameRules\Domain\Models\RuleEffect;
use Modules\Identity\Domain\Models\User;

/**
 * Change what an effect does, to what, or by how much.
 *
 * The owner is not editable, for the same reason a requirement's is not.
 */
final class UpdateRuleEffect
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, RuleEffect $effect, EffectData $data): RuleEffect
    {
        $ruleSet = $effect->ruleSet;

        if ($ruleSet === null) {
            return $effect;
        }

        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        if ($data->target !== null) {
            $effect->target = $data->target;
        }

        if ($data->sent('value')) {
            $effect->value = $data->value;
        }

        if ($data->sent('description')) {
            $effect->description = $data->description;
        }

        if ($data->effectType !== null) {
            $effect->effect_type = $data->effectType;
        }

        if ($data->sent('economy_resource_slug')) {
            $effect->economy_resource_slug = $data->economyResourceSlug;
        }

        if ($data->position !== null) {
            $effect->position = $data->position;
        }

        $changed = array_keys($effect->getDirty());

        $effect->save();

        if ($changed !== []) {
            event(new RuleEffectUpdated(
                effectId: $effect->getKey(),
                ruleSetId: $effect->rule_set_id,
                changedFields: $changed,
            ));
        }

        return $effect;
    }
}
