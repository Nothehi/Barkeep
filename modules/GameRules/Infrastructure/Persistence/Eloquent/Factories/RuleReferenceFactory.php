<?php

namespace Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameRules\Domain\Enums\ReferenceType;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleReference;

/**
 * @extends Factory<RuleReference>
 */
class RuleReferenceFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<RuleReference>
     */
    protected $model = RuleReference::class;

    /**
     * Define the model's default state.
     *
     * Both ends are left out, because a reference is *about* the pair and a
     * factory that invented two unrelated rules would produce the one shape the
     * module refuses: an edge crossing rule sets. {@see from()} is the complete
     * way to build one.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_type' => ReferenceType::RelatedTo,
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Point one rule at another.
     */
    public function from(GameRule $rule, GameRule $referenced): static
    {
        return $this->state(fn (array $attributes) => [
            'rule_id' => $rule->id,
            'referenced_rule_id' => $referenced->id,
        ]);
    }

    public function ofType(ReferenceType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'reference_type' => $type,
        ]);
    }
}
