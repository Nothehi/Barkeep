<?php

namespace Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\EvidenceType;
use Modules\PrototypeIteration\Domain\Models\DecisionEvidence;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;

/**
 * @extends Factory<DecisionEvidence>
 */
class DecisionEvidenceFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<DecisionEvidence>
     */
    protected $model = DecisionEvidence::class;

    /**
     * Define the model's default state.
     *
     * A note by default, which is the one type that needs no reference. Anything
     * else would mean inventing an id, and an invented id is a dangling citation
     * — the state this factory should make a test ask for explicitly rather than
     * hand it by accident.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'decision_id' => DesignDecision::factory(),
            'type' => EvidenceType::Note,
            'reference_id' => null,
            'description' => fake()->sentence(10),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Cite it in support of a specific decision.
     */
    public function forDecision(DesignDecision $decision): static
    {
        return $this->state(fn (array $attributes) => [
            'decision_id' => $decision->id,
            'created_by' => $decision->created_by,
        ]);
    }

    /**
     * Cite a record of a given kind.
     *
     * The type and the reference are set together, because a type that needs a
     * reference and has none is exactly what the module refuses.
     */
    public function citing(EvidenceType $type, string $referenceId): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'reference_id' => $referenceId,
        ]);
    }

    /**
     * Attribute the citation to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
