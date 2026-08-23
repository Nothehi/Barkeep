<?php

namespace Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameRules\Domain\Enums\LogicOperator;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Domain\Models\RuleSet;

/**
 * @extends Factory<ConditionGroup>
 */
class ConditionGroupFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<ConditionGroup>
     */
    protected $model = ConditionGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rule_set_id' => RuleSet::factory(),
            'name' => 'End of round check '.fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->sentence(),
            'logic_operator' => LogicOperator::And,
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

    public function combinedWith(LogicOperator $operator): static
    {
        return $this->state(fn (array $attributes) => [
            'logic_operator' => $operator,
        ]);
    }
}
