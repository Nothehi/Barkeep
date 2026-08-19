<?php

namespace Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\IterationPlaytest;

/**
 * @extends Factory<IterationPlaytest>
 */
class IterationPlaytestFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<IterationPlaytest>
     */
    protected $model = IterationPlaytest::class;

    /**
     * Define the model's default state.
     *
     * There is no default playtest, and that is the one deliberate gap in this
     * module's factories. Every other factory builds what it needs; this one
     * cannot, because building a playtest would mean this file — and therefore
     * every test that touched a link — importing Playtesting's model, which is
     * precisely the coupling the module is arranged to avoid.
     *
     * A caller supplies the id through {@see forPlaytest()}, which takes a bare
     * string. Tests that need a real playtest make one with Playtesting's own
     * factory and pass its key, so the dependency lives in the test that wanted
     * it rather than in this module's infrastructure.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'iteration_id' => Iteration::factory(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Attach the link to a specific cycle.
     */
    public function forIteration(Iteration $iteration): static
    {
        return $this->state(fn (array $attributes) => [
            'iteration_id' => $iteration->id,
            'created_by' => $iteration->created_by,
        ]);
    }

    /**
     * Point the link at a playtest, by id.
     *
     * A string rather than a model, for the reason given above the default state.
     */
    public function forPlaytest(string $playtestId): static
    {
        return $this->state(fn (array $attributes) => [
            'playtest_id' => $playtestId,
        ]);
    }

    /**
     * Attribute the connection to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
