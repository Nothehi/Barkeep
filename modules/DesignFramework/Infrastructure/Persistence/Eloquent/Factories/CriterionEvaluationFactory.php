<?php

namespace Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DesignFramework\Domain\Enums\CriterionRating;
use Modules\DesignFramework\Domain\Models\CriterionEvaluation;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\Identity\Domain\Models\User;

/**
 * One game's assessment of itself against one criterion.

 * The criterion and the adoption are built independently by default, which is the one place
 * a factory can produce data the application layer would refuse — an evaluation whose
 * criterion belongs to a version the game did not adopt. Tests that care use {@see of()},
 * which takes both and keeps them consistent; the loose default exists so a test that only
 * needs "an evaluation exists" does not have to build a whole framework.
 *
 * @extends Factory<CriterionEvaluation>
 */
class CriterionEvaluationFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<CriterionEvaluation>
     */
    protected $model = CriterionEvaluation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_framework_id' => GameFramework::factory(),
            'criterion_id' => DesignCriterion::factory(),
            'status' => CriterionRating::Good,
            'notes' => fake()->sentence(),
            'evaluated_by' => User::factory(),
            'evaluated_at' => now(),
        ];
    }

    /**
     * Record this against a specific adoption and a specific piece of content.
     *
     * The pair the module actually guarantees, so this is the state almost every test wants:
     * it keeps the record pointing at content the game's own version defines.
     */
    public function of(GameFramework $adoption, DesignCriterion $criterion): static
    {
        return $this->state(fn (array $attributes) => [
            'game_framework_id' => $adoption->id,
            'criterion_id' => $criterion->id,
            'evaluated_by' => $adoption->adopted_by,
        ]);
    }

    /**
     * Attribute the record to a specific account.
     */
    public function by(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'evaluated_by' => $user->id,
        ]);
    }
}
