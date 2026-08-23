<?php

namespace Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\PhaseTransition;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleTrigger;

/**
 * @extends Factory<PhaseTransition>
 */
class PhaseTransitionFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<PhaseTransition>
     */
    protected $model = PhaseTransition::class;

    /**
     * Define the model's default state.
     *
     * Deliberately incomplete: both ends have to be supplied through
     * {@see between()}, because a transition invented between two freshly created
     * phases in two freshly created rule sets would be precisely the shape the
     * module refuses, and a factory that produced one by default would make every
     * isolation test start from invalid data.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'condition_id' => null,
            'trigger_id' => null,
            'position' => 0,
        ];
    }

    /**
     * The only complete way to build one: both phases, from one rule set.
     */
    public function between(GamePhase $from, GamePhase $to): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_set_id' => $from->rule_set_id,
            'from_phase_id' => $from->id,
            'to_phase_id' => $to->id,
        ]);
    }

    /**
     * Guard the transition with a condition.
     */
    public function guardedBy(RuleCondition $condition): static
    {
        return $this->state(fn (array $attributes) => [
            'condition_id' => $condition->id,
        ]);
    }

    /**
     * Fire the transition off a trigger.
     */
    public function triggeredBy(RuleTrigger $trigger): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_id' => $trigger->id,
        ]);
    }

    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }
}
