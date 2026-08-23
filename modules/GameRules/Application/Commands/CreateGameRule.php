<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\GameRuleData;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\Enums\RuleType;
use Modules\GameRules\Domain\Events\GameRuleCreated;
use Modules\GameRules\Domain\Exceptions\RuleSlugIsTaken;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Write a rule down.
 *
 * The rule is created bare — no requirements, no effects, no children. That is
 * what a rule is when it is created: "we need something about line of sight"
 * comes before anybody has worked out what it says, and a form that demanded the
 * wording would stop somebody capturing the thought.
 *
 * Two identifiers may arrive in the body and both are proved to belong to this
 * rule set before anything is written: the parent it nests under and the phase it
 * applies during. The parent is additionally checked for a cycle, because the
 * database cannot see one and a rulebook whose tree has no top cannot be rendered,
 * walked or printed.
 */
final class CreateGameRule
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleSet $ruleSet, GameRuleData $data): GameRule
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $name = $data->name ?? '';
        $slug = RuleSlug::fromName($name);

        if ($this->structure->ruleSetHasRuleSlug($ruleSet, $slug)) {
            throw RuleSlugIsTaken::forRule($slug);
        }

        $parent = $data->parentRuleId === null
            ? null
            : $this->catalogue->ruleOf($ruleSet, $data->parentRuleId, 'parent_rule_id');

        $phase = $data->phaseId === null
            ? null
            : $this->catalogue->phaseOf($ruleSet, $data->phaseId);

        $rule = new GameRule;

        $rule->fill([
            'name' => $name,
            'description' => $data->description,
        ]);

        $rule->rule_set_id = $ruleSet->getKey();
        $rule->slug = $slug->value;
        $rule->parent_rule_id = $parent?->getKey();
        $rule->phase_id = $phase?->getKey();
        $rule->rule_type = $data->ruleType ?? RuleType::default();
        $rule->status = $data->status ?? RuleStatus::default();
        $rule->position = $data->position ?? $this->structure->countRuleChildren($ruleSet, $parent?->getKey());
        $rule->created_by = $actor->getKey();

        $rule->save();

        $rule->setRelation('ruleSet', $ruleSet);

        event(new GameRuleCreated(
            ruleId: $rule->getKey(),
            ruleSetId: $ruleSet->getKey(),
            slug: $slug->value,
        ));

        return $rule;
    }
}
