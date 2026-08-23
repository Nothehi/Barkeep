<?php

namespace Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameRules\Domain\Enums\MechanicCategory;
use Modules\GameRules\Domain\Models\RuleMechanic;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;

/**
 * @extends Factory<RuleMechanic>
 */
class RuleMechanicFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<RuleMechanic>
     */
    protected $model = RuleMechanic::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Worker placement', 'Deck building', 'Hand management', 'Area control',
            'Set collection', 'Auction', 'Drafting', 'Trading', 'Push your luck',
            'Dice rolling', 'Resource management', 'Simultaneous action selection',
        ]).' '.fake()->unique()->numberBetween(1, 9999);

        return [
            'rule_set_id' => RuleSet::factory(),
            'name' => $name,
            'slug' => RuleSlug::fromName($name)->value,
            'description' => fake()->sentence(),
            'category' => MechanicCategory::Other,
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
            'slug' => RuleSlug::fromName($name)->value,
        ]);
    }

    public function inCategory(MechanicCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }
}
