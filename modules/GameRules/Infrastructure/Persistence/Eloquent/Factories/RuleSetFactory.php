<?php

namespace Modules\GameRules\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Enums\RuleSetStatus;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\Identity\Domain\Models\User;

/**
 * @extends Factory<RuleSet>
 */
class RuleSetFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<RuleSet>
     */
    protected $model = RuleSet::class;

    /**
     * Define the model's default state.
     *
     * The version is created with its own game rather than left to a caller,
     * because a rule set with no design state behind it is data no command in
     * this module could produce — and tests built on impossible data prove
     * nothing.
     *
     * The default status is draft, and that matters more here than in most
     * factories: a factory that produced active rule sets by default would make
     * the "one active per version" index fire the second time any test created
     * two, and would make every child factory's writes refuse, since only a draft
     * accepts them.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_version_id' => fn (): string => GameVersion::factory()
                ->create([
                    'game_id' => Game::factory(),
                    'created_by' => User::factory(),
                ])->id,
            'name' => rtrim(fake()->sentence(2), '.').' rules',
            'description' => fake()->sentence(),
            'status' => RuleSetStatus::Draft,
            'cloned_from_rule_set_id' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Write the rule set for a specific design state.
     */
    public function forVersion(GameVersion $version): static
    {
        return $this->state(fn (array $attributes) => [
            'game_version_id' => $version->id,
            'created_by' => $version->created_by,
        ]);
    }

    /**
     * Write the rule set for a game, cutting a fresh design state for it.
     */
    public function forGame(Game $game): static
    {
        return $this->state(fn (array $attributes) => [
            'game_version_id' => GameVersion::factory()->nextFor($game)->create()->id,
            'created_by' => $game->created_by,
        ]);
    }

    /**
     * Give the rule set a specific name.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Put the rule set at a specific point in its lifecycle.
     */
    public function withStatus(RuleSetStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Indicate that this is the rule system in play.
     */
    public function active(): static
    {
        return $this->withStatus(RuleSetStatus::Active);
    }

    /**
     * Indicate that the rule set has been put away.
     */
    public function archived(): static
    {
        return $this->withStatus(RuleSetStatus::Archived);
    }

    /**
     * Attribute the rule set to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
