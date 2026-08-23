<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\MechanicData;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Enums\MechanicCategory;
use Modules\GameRules\Domain\Events\RuleMechanicCreated;
use Modules\GameRules\Domain\Exceptions\RuleSlugIsTaken;
use Modules\GameRules\Domain\Models\RuleMechanic;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Name a mechanism this rule system uses.
 *
 * Worker placement, deck building, push your luck. Naming them is the cheapest
 * useful thing a designer can do to a rule set — eight lines that tell any reader
 * what family of game this is before they read a single rule.
 *
 * The record is this rule set's own, in the studio's own words. It is not an
 * entry in GameDesign's shared vocabulary of design terms, and nothing here
 * writes to that catalogue — see {@see RuleMechanic} for why the two exist.
 */
final class CreateMechanic
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleSet $ruleSet, MechanicData $data): RuleMechanic
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $name = $data->name ?? '';
        $slug = RuleSlug::fromName($name);

        if ($this->structure->ruleSetHasMechanicSlug($ruleSet, $slug)) {
            throw RuleSlugIsTaken::forMechanic($slug);
        }

        $mechanic = new RuleMechanic;

        $mechanic->fill([
            'name' => $name,
            'description' => $data->description,
        ]);

        $mechanic->rule_set_id = $ruleSet->getKey();
        $mechanic->slug = $slug->value;
        $mechanic->category = $data->category ?? MechanicCategory::default();
        $mechanic->position = $data->position ?? $this->structure->mechanicsOf($ruleSet)->count();

        $mechanic->save();

        $mechanic->setRelation('ruleSet', $ruleSet);

        event(new RuleMechanicCreated(
            mechanicId: $mechanic->getKey(),
            ruleSetId: $ruleSet->getKey(),
            slug: $slug->value,
        ));

        return $mechanic;
    }
}
