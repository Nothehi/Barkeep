<?php

namespace Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameRules\Domain\Models\DefeatCondition;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleSet;

/**
 * @extends Factory<DefeatCondition>
 */
class DefeatConditionFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<DefeatCondition>
     */
    protected $model = DefeatCondition::class;

    /**
     * Define the model's default state.
     *
     * No condition, which is how outcomes are usually written: the sentence goes
     * in on day one and the condition that measures it comes later, if at all.
     * {@see measuredBy()} attaches one.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rule_set_id' => RuleSet::factory(),
            'name' => 'Health reaches zero '.fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->sentence(),
            'condition_id' => null,
            'priority' => 0,
        ];
    }

    public function forRuleSet(RuleSet $ruleSet): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_set_id' => $ruleSet->id,
        ]);
    }

    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Give the outcome something that says when it has been met.
     */
    public function measuredBy(RuleCondition $condition): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_set_id' => $condition->rule_set_id,
            'condition_id' => $condition->id,
        ]);
    }

    /**
     * Say which outcome is checked first.
     *
     * Games routinely have several, and the order settles ties: "control three
     * territories" beating "most points" is the rule, not a display preference.
     */
    public function atPriority(int $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }
}
