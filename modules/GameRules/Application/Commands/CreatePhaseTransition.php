<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\PhaseTransitionData;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\PhaseTransitionCreated;
use Modules\GameRules\Domain\Exceptions\InvalidPhaseTransition;
use Modules\GameRules\Domain\Models\PhaseTransition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Say how play moves from one phase to the next.
 *
 * Four identifiers arrive in the body — both phases, and optionally a condition
 * and a trigger — and every one is resolved *through* the rule set before anything
 * is written. That is the whole of section 14 of the brief: a transition whose
 * ends belong to two different rule systems would be an edge between two games.
 *
 * A transition to the phase it starts from is refused. It is never what somebody
 * meant — play arriving where it already is has not advanced — and it grows a
 * self-loop in the diagram that makes every other arrow harder to read. A phase
 * that genuinely repeats is a round, and the edge belongs on the round's boundary.
 *
 * Both guards are optional, and most transitions have neither: the commonest edge
 * in a board game is unconditional and automatic.
 */
final class CreatePhaseTransition
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleSet $ruleSet, PhaseTransitionData $data): PhaseTransition
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $from = $this->catalogue->phaseOf($ruleSet, $data->fromPhaseId ?? '', 'from_phase_id');
        $to = $this->catalogue->phaseOf($ruleSet, $data->toPhaseId ?? '', 'to_phase_id');

        if ($from->getKey() === $to->getKey()) {
            throw InvalidPhaseTransition::loopsOnItself($from->getKey());
        }

        $condition = $data->conditionId === null
            ? null
            : $this->catalogue->conditionOf($ruleSet, $data->conditionId);

        $trigger = $data->triggerId === null
            ? null
            : $this->catalogue->triggerOf($ruleSet, $data->triggerId);

        $transition = new PhaseTransition;

        $transition->rule_set_id = $ruleSet->getKey();
        $transition->from_phase_id = $from->getKey();
        $transition->to_phase_id = $to->getKey();
        $transition->condition_id = $condition?->getKey();
        $transition->trigger_id = $trigger?->getKey();
        $transition->position = $data->position ?? $this->structure->countTransitionsFrom($ruleSet, $from->getKey());

        $transition->save();

        $transition->setRelation('ruleSet', $ruleSet);
        $transition->setRelation('fromPhase', $from);
        $transition->setRelation('toPhase', $to);

        event(new PhaseTransitionCreated(
            transitionId: $transition->getKey(),
            ruleSetId: $ruleSet->getKey(),
            fromPhaseId: $from->getKey(),
        ));

        return $transition;
    }
}
