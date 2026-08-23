<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Commands\Concerns\ResolvesRecordOwner;
use Modules\GameRules\Application\DTOs\EffectData;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Enums\EffectType;
use Modules\GameRules\Domain\Events\RuleEffectCreated;
use Modules\GameRules\Domain\Models\RuleEffect;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Record what happens when a rule or an action resolves.
 *
 * Structured, and not executable. "RESOURCE / Victory points / +3" is what the
 * rulebook says, not an instruction anything will carry out — section 33 of the
 * brief keeps the execution engine in a bounded context that does not exist yet,
 * and this command writing three columns rather than a script is what keeps that
 * true.
 *
 * The value stays a string so "half, rounded down" is sayable. The validator
 * notices when a type that implies an amount does not carry one.
 */
final class CreateRuleEffect
{
    use ResolvesRecordOwner;

    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleSet $ruleSet, EffectData $data): RuleEffect
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        [$ruleId, $actionId] = $this->resolveOwner(
            $this->catalogue,
            $ruleSet,
            $data->ruleId,
            $data->actionId,
            forEffect: true,
        );

        $effect = new RuleEffect;

        $effect->fill([
            'target' => $data->target ?? '',
            'value' => $data->value,
            'description' => $data->description,
        ]);

        $effect->rule_set_id = $ruleSet->getKey();
        $effect->rule_id = $ruleId;
        $effect->action_id = $actionId;
        $effect->effect_type = $data->effectType ?? EffectType::default();
        $effect->economy_resource_slug = $data->economyResourceSlug;
        $effect->position = $data->position ?? $this->structure->countEffectsFor($ruleSet, $ruleId, $actionId);

        $effect->save();

        event(new RuleEffectCreated(
            effectId: $effect->getKey(),
            ruleSetId: $ruleSet->getKey(),
            target: $effect->target,
        ));

        return $effect;
    }
}
