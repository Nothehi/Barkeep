<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\MechanicData;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\RuleMechanicUpdated;
use Modules\GameRules\Domain\Exceptions\RuleSlugIsTaken;
use Modules\GameRules\Domain\Models\RuleMechanic;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Rename a mechanism, recategorise it, or say more about how this game uses it.
 */
final class UpdateMechanic
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleMechanic $mechanic, MechanicData $data): RuleMechanic
    {
        $ruleSet = $mechanic->ruleSet;

        if ($ruleSet === null) {
            return $mechanic;
        }

        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        if ($data->name !== null && $data->name !== $mechanic->name) {
            $slug = RuleSlug::fromName($data->name);

            if ($this->structure->ruleSetHasMechanicSlug($ruleSet, $slug, $mechanic->getKey())) {
                throw RuleSlugIsTaken::forMechanic($slug);
            }

            $mechanic->name = $data->name;
            $mechanic->slug = $slug->value;
        }

        if ($data->sent('description')) {
            $mechanic->description = $data->description;
        }

        if ($data->category !== null) {
            $mechanic->category = $data->category;
        }

        if ($data->position !== null) {
            $mechanic->position = $data->position;
        }

        $changed = array_keys($mechanic->getDirty());

        $mechanic->save();

        if ($changed !== []) {
            event(new RuleMechanicUpdated(
                mechanicId: $mechanic->getKey(),
                ruleSetId: $mechanic->rule_set_id,
                changedFields: $changed,
            ));
        }

        return $mechanic;
    }
}
