<?php

namespace Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\Models\ScenarioVariable;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;

/**
 * @extends Factory<ScenarioVariable>
 */
class ScenarioVariableFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<ScenarioVariable>
     */
    protected $model = ScenarioVariable::class;

    /**
     * Define the model's default state.
     *
     * The variable is built inside the scenario's own profile, because an
     * override of a variable from a different configuration is data no command
     * would accept.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scenario_id' => BalanceScenario::factory(),
            'balance_variable_id' => function (array $attributes): string {
                $scenario = BalanceScenario::query()->whereKey($attributes['scenario_id'])->firstOrFail();

                return BalanceVariable::factory()
                    ->create(['balance_profile_id' => $scenario->balance_profile_id])->id;
            },
            'value' => '15.000000',
        ];
    }

    /**
     * State a value for a specific variable in a specific scenario.
     */
    public function of(BalanceScenario $scenario, BalanceVariable $variable, string $value): static
    {
        return $this->state(fn (array $attributes) => [
            'scenario_id' => $scenario->id,
            'balance_variable_id' => $variable->id,
            'value' => Quantity::from($value)->toStorage(),
        ]);
    }
}
