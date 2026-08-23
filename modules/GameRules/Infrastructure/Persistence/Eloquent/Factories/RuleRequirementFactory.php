<?php

namespace Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameRules\Domain\Enums\RequirementType;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleRequirement;

/**
 * @extends Factory<RuleRequirement>
 */
class RuleRequirementFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<RuleRequirement>
     */
    protected $model = RuleRequirement::class;

    /**
     * Define the model's default state.
     *
     * No owner, because a requirement has exactly one and the factory cannot
     * guess which. {@see forAction()} and {@see forRule()} are the two complete
     * ways to build one; a requirement created without either is the shape the
     * validator reports as an error, and being able to build it on purpose is
     * what lets that test exist.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'action_id' => null,
            'rule_id' => null,
            'requirement_type' => RequirementType::Custom,
            'description' => fake()->sentence(8),
            'value' => null,
            'economy_resource_slug' => null,
            'position' => 0,
        ];
    }

    /**
     * Gate an action on this requirement.
     */
    public function forAction(RuleAction $action): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_set_id' => $action->rule_set_id,
            'action_id' => $action->id,
            'rule_id' => null,
        ]);
    }

    /**
     * Gate a rule on this requirement.
     */
    public function forRule(GameRule $rule): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_set_id' => $rule->rule_set_id,
            'rule_id' => $rule->id,
            'action_id' => null,
        ]);
    }

    public function ofType(RequirementType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'requirement_type' => $type,
        ]);
    }

    /**
     * Price the requirement in one of the game's resources, by handle.
     */
    public function costing(string $economyResourceSlug, string $value): static
    {
        return $this->state(fn (array $attributes) => [
            'requirement_type' => RequirementType::Resource,
            'economy_resource_slug' => $economyResourceSlug,
            'value' => $value,
        ]);
    }
}
