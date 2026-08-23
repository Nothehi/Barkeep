<?php

namespace Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\Enums\RuleType;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;
use Modules\Identity\Domain\Models\User;

/**
 * @extends Factory<GameRule>
 */
class GameRuleFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<GameRule>
     */
    protected $model = GameRule::class;

    /**
     * Define the model's default state.
     *
     * The names are unique because the slug derived from them has to be unique
     * inside a set, and a factory that produced two "Combat" rules would fail on
     * the index rather than on anything a test meant to assert.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Combat', 'Movement', 'Trading', 'Line of sight', 'Turn order',
            'Drawing cards', 'Scoring', 'Blocking', 'Setup', 'Building',
            'Recruiting', 'Bidding', 'Passing', 'Upkeep', 'Reinforcement',
        ]).' '.fake()->unique()->numberBetween(1, 9999);

        return [
            'rule_set_id' => RuleSet::factory(),
            'parent_rule_id' => null,
            'phase_id' => null,
            'name' => $name,
            'slug' => RuleSlug::fromName($name)->value,
            'description' => fake()->sentence(12),
            'rule_type' => RuleType::General,
            'status' => RuleStatus::Active,
            'position' => 0,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Write the rule inside a specific rule set.
     */
    public function forRuleSet(RuleSet $ruleSet): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_set_id' => $ruleSet->id,
            'created_by' => $ruleSet->created_by,
        ]);
    }

    /**
     * Give the rule a specific name, and the handle that follows from it.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => RuleSlug::fromName($name)->value,
        ]);
    }

    /**
     * Nest the rule under another.
     */
    public function under(GameRule $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_set_id' => $parent->rule_set_id,
            'parent_rule_id' => $parent->id,
        ]);
    }

    /**
     * Attach the rule to a phase of play.
     */
    public function during(GamePhase $phase): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_set_id' => $phase->rule_set_id,
            'phase_id' => $phase->id,
        ]);
    }

    /**
     * File the rule under a particular part of the game.
     */
    public function ofType(RuleType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_type' => $type,
        ]);
    }

    /**
     * Mark the rule as kept for the record rather than in play.
     */
    public function deprecated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RuleStatus::Deprecated,
        ]);
    }

    /**
     * Place the rule at a specific point in the designer's ordering.
     */
    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }
}
