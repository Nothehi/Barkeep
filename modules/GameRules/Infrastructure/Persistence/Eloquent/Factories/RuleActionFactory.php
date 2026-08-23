<?php

namespace Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameRules\Domain\Enums\RuleActionType;
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;

/**
 * @extends Factory<RuleAction>
 */
class RuleActionFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<RuleAction>
     */
    protected $model = RuleAction::class;

    /**
     * Define the model's default state.
     *
     * No phase, which is deliberate: an action is created before the turn
     * structure is settled, and the validator reports the gap. A factory that
     * quietly invented a phase would make it impossible to write the test that
     * asserts the finding.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Build', 'Move', 'Attack', 'Trade', 'Draw card', 'Pass',
            'Recruit', 'Upgrade', 'Explore', 'Harvest', 'Research', 'Rest',
        ]).' '.fake()->unique()->numberBetween(1, 9999);

        return [
            'rule_set_id' => RuleSet::factory(),
            'phase_id' => null,
            'name' => $name,
            'slug' => RuleSlug::fromName($name)->value,
            'description' => fake()->sentence(),
            'action_type' => RuleActionType::Basic,
            'status' => RuleStatus::Active,
            'economy_action_slug' => null,
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

    /**
     * Say when the action may be taken.
     */
    public function during(GamePhase $phase): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_set_id' => $phase->rule_set_id,
            'phase_id' => $phase->id,
        ]);
    }

    public function ofType(RuleActionType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => $type,
        ]);
    }

    /**
     * Wire the action to an economy action by handle.
     *
     * A handle rather than a model, because that is all this module ever holds —
     * see `EconomyDirectory`.
     */
    public function pricedAs(string $economyActionSlug): static
    {
        return $this->state(fn (array $attributes) => [
            'economy_action_slug' => $economyActionSlug,
        ]);
    }

    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }
}
