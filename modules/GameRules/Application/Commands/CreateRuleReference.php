<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\ReferenceData;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Enums\ReferenceType;
use Modules\GameRules\Domain\Events\RuleReferenceCreated;
use Modules\GameRules\Domain\Exceptions\InvalidRuleReference;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleReference;
use Modules\GameRules\Infrastructure\Analysis\CycleDetector;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Record how one rule relates to another.
 *
 *     Siege  ── exception to ──▶  Combat
 *
 * Three refusals, in order of how likely they are.
 *
 * A rule pointing at itself is meaningless under every reference type, so it is
 * refused outright.
 *
 * A rule from another rule set is refused because `rule_references` has no
 * `rule_set_id` of its own — the referenced rule is resolved *through* the
 * referring rule's set, which makes "both ends belong to the same rule system" a
 * fact about how the lookup works rather than a check somebody has to remember.
 *
 * A reference that would close a loop among the *directed* kinds is refused
 * because neither rule could then be read first. `related to` is symmetric and
 * carries no order, so a mutual one is a note rather than a contradiction and
 * passes.
 *
 * A duplicate is a no-op. Saying the same thing twice is a double-click.
 */
final class CreateRuleReference
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleCatalogue $catalogue,
        private readonly CycleDetector $cycles,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, GameRule $rule, ReferenceData $data): RuleReference
    {
        $ruleSet = $rule->ruleSet;

        if ($ruleSet !== null) {
            $this->guard->ensureRuleSetAcceptsChanges($ruleSet);
        }

        $referencedRuleId = $data->referencedRuleId ?? '';

        if ($referencedRuleId === $rule->getKey()) {
            throw InvalidRuleReference::toItself($rule->getKey());
        }

        $referenced = $this->catalogue->referencedRuleFor($rule, $referencedRuleId);
        $type = $data->referenceType ?? ReferenceType::default();

        $existing = RuleReference::query()
            ->where('rule_id', $rule->getKey())
            ->where('referenced_rule_id', $referenced->getKey())
            ->where('reference_type', $type)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        if ($type->isDirected() && $ruleSet !== null) {
            $edges = $this->structure->directedReferenceMap($ruleSet);

            if ($this->cycles->wouldCloseLoop($edges, $rule->getKey(), $referenced->getKey())) {
                throw InvalidRuleReference::wouldCycle($rule->getKey(), $referenced->getKey());
            }
        }

        $reference = new RuleReference;

        $reference->fill(['description' => $data->description]);
        $reference->rule_id = $rule->getKey();
        $reference->referenced_rule_id = $referenced->getKey();
        $reference->reference_type = $type;

        $reference->save();

        $reference->setRelation('rule', $rule);
        $reference->setRelation('referencedRule', $referenced);

        event(new RuleReferenceCreated(
            referenceId: $reference->getKey(),
            ruleId: $rule->getKey(),
            referencedRuleId: $referenced->getKey(),
            referenceType: $type,
        ));

        return $reference;
    }
}
