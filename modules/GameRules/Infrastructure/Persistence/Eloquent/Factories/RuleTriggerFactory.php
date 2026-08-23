<?php

namespace Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameRules\Domain\Enums\TriggerType;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\RuleTrigger;

/**
 * @extends Factory<RuleTrigger>
 */
class RuleTriggerFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<RuleTrigger>
     */
    protected $model = RuleTrigger::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rule_set_id' => RuleSet::factory(),
            'name' => 'At the start of round '.fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->sentence(),
            'trigger_type' => TriggerType::RoundStart,
            'position' => 0,
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

    public function ofType(TriggerType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => $type,
        ]);
    }
}
