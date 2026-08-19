<?php

namespace Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\Iteration;

/**
 * @extends Factory<DesignDecision>
 */
class DesignDecisionFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<DesignDecision>
     */
    protected $model = DesignDecision::class;

    /**
     * Define the model's default state.
     *
     * Proposed, with nobody named as having settled it. That pairing is enforced
     * by the states below rather than left to a caller: a decision with a
     * `decided_by` and a status of proposed would be a record claiming somebody
     * agreed to something they have not, which no command can produce.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'iteration_id' => Iteration::factory(),
            'title' => rtrim(fake()->sentence(3), '.'),
            'decision' => ucfirst(fake()->sentence(8)),
            'reason' => fake()->sentence(14),
            'status' => DecisionStatus::Proposed,
            'decided_by' => null,
            'decided_at' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Propose the decision inside a specific cycle.
     */
    public function forIteration(Iteration $iteration): static
    {
        return $this->state(fn (array $attributes) => [
            'iteration_id' => $iteration->id,
            'created_by' => $iteration->created_by,
        ]);
    }

    /**
     * Put the decision at a specific point in its lifecycle.
     *
     * Attribution travels with the status. A settled decision always names who
     * settled it and when, and a proposed one never does — which keeps a
     * factory-made decision from reading as agreed by nobody.
     */
    public function withStatus(DecisionStatus $status, ?User $decider = null): static
    {
        return $this->state(fn (array $attributes) => $status === DecisionStatus::Proposed
            ? [
                'status' => $status,
                'decided_by' => null,
                'decided_at' => null,
            ]
            : [
                'status' => $status,
                'decided_by' => $decider !== null ? $decider->id : $attributes['created_by'],
                'decided_at' => now(),
            ]);
    }

    /**
     * Indicate that the studio agreed.
     */
    public function accepted(?User $decider = null): static
    {
        return $this->withStatus(DecisionStatus::Accepted, $decider);
    }

    /**
     * Indicate that the studio decided against it.
     */
    public function rejected(?User $decider = null): static
    {
        return $this->withStatus(DecisionStatus::Rejected, $decider);
    }

    /**
     * Indicate that the studio put it off.
     */
    public function deferred(?User $decider = null): static
    {
        return $this->withStatus(DecisionStatus::Deferred, $decider);
    }

    /**
     * Give the decision a specific title.
     */
    public function titled(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }

    /**
     * Attribute the proposal to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
