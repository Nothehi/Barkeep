<?php

namespace Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameEconomy\Domain\Enums\ActionEffectType;
use Modules\GameEconomy\Domain\Models\ActionEffect;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;

/**
 * @extends Factory<ActionEffect>
 */
class ActionEffectFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<ActionEffect>
     */
    protected $model = ActionEffect::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'action_id' => EconomyAction::factory(),
            'effect_type' => ActionEffectType::Unlock,
            'target' => 'Building II',
            'value' => null,
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Attach the effect to a specific action.
     */
    public function forAction(EconomyAction $action): static
    {
        return $this->state(fn (array $attributes) => [
            'action_id' => $action->id,
        ]);
    }

    /**
     * Make it a particular kind of effect, aimed at a particular thing.
     */
    public function of(ActionEffectType $type, string $target, ?string $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'effect_type' => $type,
            'target' => $target,
            'value' => Quantity::fromNullable($value)?->toStorage(),
        ]);
    }
}
