<?php

namespace Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameRules\Domain\Enums\GamePhaseType;
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;

/**
 * @extends Factory<GamePhase>
 */
class GamePhaseFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<GamePhase>
     */
    protected $model = GamePhase::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Setup', 'Round start', 'Action phase', 'Resolution', 'Cleanup',
            'Upkeep', 'Combat phase', 'Trading phase', 'Scoring', 'Game end',
        ]).' '.fake()->unique()->numberBetween(1, 9999);

        return [
            'rule_set_id' => RuleSet::factory(),
            'parent_phase_id' => null,
            'name' => $name,
            'slug' => RuleSlug::fromName($name)->value,
            'description' => fake()->sentence(),
            'phase_type' => GamePhaseType::Round,
            'status' => RuleStatus::Active,
            'position' => 0,
        ];
    }

    /**
     * Write the phase inside a specific rule set.
     */
    public function forRuleSet(RuleSet $ruleSet): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_set_id' => $ruleSet->id,
        ]);
    }

    /**
     * Give the phase a specific name, and the handle that follows from it.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => RuleSlug::fromName($name)->value,
        ]);
    }

    /**
     * Nest the phase inside another.
     */
    public function under(GamePhase $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_set_id' => $parent->rule_set_id,
            'parent_phase_id' => $parent->id,
        ]);
    }

    /**
     * Say what kind of stage of play this is.
     */
    public function ofType(GamePhaseType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'phase_type' => $type,
        ]);
    }

    /**
     * Mark the phase as the one play begins at.
     */
    public function setup(): static
    {
        return $this->ofType(GamePhaseType::Setup);
    }

    /**
     * Mark the phase as the one play stops at.
     */
    public function terminal(): static
    {
        return $this->ofType(GamePhaseType::EndGame);
    }

    /**
     * Place the phase at a specific point in the turn structure.
     */
    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }
}
