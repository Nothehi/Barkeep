<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\RuleActionData;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Enums\RuleActionType;
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\Events\RuleActionCreated;
use Modules\GameRules\Domain\Exceptions\RuleSlugIsTaken;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Declare something a player may do.
 *
 * Created bare — no phase, no requirements, no effects — because "we need a Build
 * action" comes before anybody has decided when it can be taken or what it costs.
 * The validator reports all three gaps, which is exactly the checklist somebody
 * needs and is why the phase is nullable in the schema despite being an error to
 * leave out.
 *
 * `economy_action_slug` is a handle, and the only thing this module ever stores
 * about what an action costs. The costs themselves belong to GameEconomy and are
 * read live — see section 16 of the brief and `EconomyDirectory`. Nothing here
 * validates the handle: a rule set written before the economy exists is the
 * ordinary case, and the validator mentions an unresolved handle only once there
 * is an economy for it to be missing from.
 */
final class CreateRuleAction
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleSet $ruleSet, RuleActionData $data): RuleAction
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $name = $data->name ?? '';
        $slug = RuleSlug::fromName($name);

        if ($this->structure->ruleSetHasActionSlug($ruleSet, $slug)) {
            throw RuleSlugIsTaken::forAction($slug);
        }

        $phase = $data->phaseId === null
            ? null
            : $this->catalogue->phaseOf($ruleSet, $data->phaseId);

        $action = new RuleAction;

        $action->fill([
            'name' => $name,
            'description' => $data->description,
        ]);

        $action->rule_set_id = $ruleSet->getKey();
        $action->slug = $slug->value;
        $action->phase_id = $phase?->getKey();
        $action->action_type = $data->actionType ?? RuleActionType::default();
        $action->status = $data->status ?? RuleStatus::default();
        $action->economy_action_slug = $data->economyActionSlug;
        $action->position = $data->position ?? $this->structure->actionsOf($ruleSet)->count();

        $action->save();

        $action->setRelation('ruleSet', $ruleSet);

        event(new RuleActionCreated(
            actionId: $action->getKey(),
            ruleSetId: $ruleSet->getKey(),
            slug: $slug->value,
        ));

        return $action;
    }
}
