<?php

namespace Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameRules\Domain\Enums\EffectType;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleEffect;

/**
 * @extends Factory<RuleEffect>
 */
class RuleEffectFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<RuleEffect>
     */
    protected $model = RuleEffect::class;

    /**
     * Define the model's default state.
     *
     * A `state_change` by default, which is one of the types that needs no
     * amount — so the default effect draws no finding. Owners work the same way
     * as a requirement's: neither is set here, and the two `for*` states are the
     * complete ways to build one.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'action_id' => null,
            'rule_id' => null,
            'effect_type' => EffectType::StateChange,
            'target' => fake()->randomElement(['The active player', 'The board', 'The deck', 'All players']),
            'value' => null,
            'description' => fake()->sentence(),
            'economy_resource_slug' => null,
            'position' => 0,
        ];
    }

    /**
     * Make this what an action does.
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
     * Make this what a rule does.
     */
    public function forRule(GameRule $rule): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_set_id' => $rule->rule_set_id,
            'rule_id' => $rule->id,
            'action_id' => null,
        ]);
    }

    public function ofType(EffectType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'effect_type' => $type,
        ]);
    }

    /**
     * Move a quantity of something.
     */
    public function moving(string $target, string $value): static
    {
        return $this->state(fn (array $attributes) => [
            'effect_type' => EffectType::Resource,
            'target' => $target,
            'value' => $value,
        ]);
    }

    /**
     * Point the effect at one of the game's resources, by handle.
     */
    public function inResource(string $economyResourceSlug): static
    {
        return $this->state(fn (array $attributes) => [
            'economy_resource_slug' => $economyResourceSlug,
        ]);
    }
}
