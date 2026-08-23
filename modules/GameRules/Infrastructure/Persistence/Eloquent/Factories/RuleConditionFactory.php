<?php

namespace Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameRules\Domain\Enums\ConditionOperator;
use Modules\GameRules\Domain\Enums\ConditionType;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleSet;

/**
 * @extends Factory<RuleCondition>
 */
class RuleConditionFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<RuleCondition>
     */
    protected $model = RuleCondition::class;

    /**
     * Define the model's default state.
     *
     * A numeric value against a numeric operator, so the default condition is one
     * the validator has nothing to say about — a factory whose every row produced
     * a finding would make it impossible to assert that a clean rule set is clean.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rule_set_id' => RuleSet::factory(),
            'name' => 'Score reaches '.fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->sentence(),
            'condition_type' => ConditionType::Score,
            'operator' => ConditionOperator::GreaterThanOrEqual,
            'value' => (string) fake()->numberBetween(1, 50),
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
     * State the condition explicitly.
     */
    public function comparing(ConditionType $type, ConditionOperator $operator, ?string $value): static
    {
        return $this->state(fn (array $attributes) => [
            'condition_type' => $type,
            'operator' => $operator,
            'value' => $value,
        ]);
    }

    /**
     * A condition whose operator needs no value.
     */
    public function unary(ConditionOperator $operator = ConditionOperator::IsTrue): static
    {
        return $this->state(fn (array $attributes) => [
            'operator' => $operator,
            'value' => null,
        ]);
    }
}
