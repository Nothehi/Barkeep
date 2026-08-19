<?php

namespace Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;

/**
 * @extends Factory<DesignExperiment>
 */
class DesignExperimentFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<DesignExperiment>
     */
    protected $model = DesignExperiment::class;

    /**
     * Define the model's default state.
     *
     * The before half is filled in and the after half is not, which is the shape
     * of a planned experiment: a question, a prediction and a method, with no
     * result yet. The two halves are only ever both populated by
     * {@see completed()}, so a factory-made experiment cannot accidentally look
     * like one whose prediction was written after the fact.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'iteration_id' => Iteration::factory(),
            'title' => rtrim(fake()->sentence(3), '.').' test',
            'question' => ucfirst(fake()->sentence(8)),
            'hypothesis' => fake()->sentence(),
            'method' => fake()->sentence(),
            'expected_result' => fake()->sentence(),
            'actual_result' => null,
            'conclusion' => null,
            'status' => ExperimentStatus::Planned,
            'started_at' => null,
            'completed_at' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Design the experiment inside a specific cycle.
     */
    public function forIteration(Iteration $iteration): static
    {
        return $this->state(fn (array $attributes) => [
            'iteration_id' => $iteration->id,
            'created_by' => $iteration->created_by,
        ]);
    }

    /**
     * Put the experiment at a specific point in its lifecycle.
     *
     * The result appears with completion and only with completion, so the record
     * always reads as something that was run before it was answered.
     */
    public function withStatus(ExperimentStatus $status): static
    {
        return $this->state(fn (array $attributes) => match ($status) {
            ExperimentStatus::Planned => [
                'status' => $status,
                'started_at' => null,
                'completed_at' => null,
            ],
            ExperimentStatus::Running => [
                'status' => $status,
                'started_at' => now(),
                'completed_at' => null,
            ],
            ExperimentStatus::Completed => [
                'status' => $status,
                'started_at' => now()->subHours(3),
                'completed_at' => now(),
                'actual_result' => $attributes['actual_result'] ?? fake()->sentence(12),
                'conclusion' => $attributes['conclusion'] ?? fake()->sentence(10),
            ],
            ExperimentStatus::Cancelled => [
                'status' => $status,
                'completed_at' => null,
            ],
        });
    }

    /**
     * Indicate that the experiment is under way.
     */
    public function running(): static
    {
        return $this->withStatus(ExperimentStatus::Running);
    }

    /**
     * Indicate that the experiment produced a result.
     */
    public function completed(): static
    {
        return $this->withStatus(ExperimentStatus::Completed);
    }

    /**
     * Indicate that the question was abandoned.
     */
    public function cancelled(): static
    {
        return $this->withStatus(ExperimentStatus::Cancelled);
    }

    /**
     * Leave the prediction unstated, the way exploratory work does.
     */
    public function withoutHypothesis(): static
    {
        return $this->state(fn (array $attributes) => [
            'hypothesis' => null,
            'expected_result' => null,
        ]);
    }

    /**
     * Record what happened without saying what it means.
     *
     * The state a real experiment sits in for days: the session is over, the
     * result is written down, and nobody has decided yet what to take from it.
     */
    public function withoutConclusion(): static
    {
        return $this->completed()->state(fn (array $attributes) => [
            'conclusion' => null,
        ]);
    }

    /**
     * Attribute the experiment to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
