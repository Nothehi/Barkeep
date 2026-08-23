<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\GamePhaseData;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Enums\GamePhaseType;
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\Events\GamePhaseCreated;
use Modules\GameRules\Domain\Exceptions\RuleSlugIsTaken;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Add a stage of play.
 *
 * A phase of the *game* — setup, the action phase, cleanup — and not a phase of
 * the designer's work, which is DesignFramework's and unrelated.
 *
 * Created with no transitions, because the phase it would lead to usually does not
 * exist yet. The validator reports the missing exit; wiring the phases together is
 * what the phase designer is for.
 *
 * The default position puts it at the end of its level, which is what somebody
 * adding "Cleanup" after "Resolution" means.
 */
final class CreateGamePhase
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleSet $ruleSet, GamePhaseData $data): GamePhase
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $name = $data->name ?? '';
        $slug = RuleSlug::fromName($name);

        if ($this->structure->ruleSetHasPhaseSlug($ruleSet, $slug)) {
            throw RuleSlugIsTaken::forPhase($slug);
        }

        $parent = $data->parentPhaseId === null
            ? null
            : $this->catalogue->phaseOf($ruleSet, $data->parentPhaseId, 'parent_phase_id');

        $phase = new GamePhase;

        $phase->fill([
            'name' => $name,
            'description' => $data->description,
        ]);

        $phase->rule_set_id = $ruleSet->getKey();
        $phase->slug = $slug->value;
        $phase->parent_phase_id = $parent?->getKey();
        $phase->phase_type = $data->phaseType ?? GamePhaseType::default();
        $phase->status = $data->status ?? RuleStatus::default();
        $phase->position = $data->position ?? $this->structure->countPhaseChildren($ruleSet, $parent?->getKey());

        $phase->save();

        $phase->setRelation('ruleSet', $ruleSet);

        event(new GamePhaseCreated(
            phaseId: $phase->getKey(),
            ruleSetId: $ruleSet->getKey(),
            slug: $slug->value,
        ));

        return $phase;
    }
}
