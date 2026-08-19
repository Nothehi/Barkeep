<?php

namespace Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Domain\Enums\BalanceProfileStatus;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\Identity\Domain\Models\User;

/**
 * @extends Factory<BalanceProfile>
 */
class BalanceProfileFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<BalanceProfile>
     */
    protected $model = BalanceProfile::class;

    /**
     * Define the model's default state.
     *
     * The version is created with its own game rather than left to a caller,
     * because a profile with no design state behind it is data no command in
     * this module could produce — and tests built on impossible data prove
     * nothing.
     *
     * The default status is draft. A factory that produced active profiles by
     * default would make the "one active per version" index fire the second time
     * any test created two.
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
            'name' => rtrim(fake()->sentence(2), '.').' balance',
            'description' => fake()->sentence(),
            'status' => BalanceProfileStatus::Draft,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Configure the profile for a specific design state.
     */
    public function forVersion(GameVersion $version): static
    {
        return $this->state(fn (array $attributes) => [
            'game_version_id' => $version->id,
            'created_by' => $version->created_by,
        ]);
    }

    /**
     * Configure the profile for a game, cutting a fresh design state for it.
     */
    public function forGame(Game $game): static
    {
        return $this->state(fn (array $attributes) => [
            'game_version_id' => GameVersion::factory()->nextFor($game)->create()->id,
            'created_by' => $game->created_by,
        ]);
    }

    /**
     * Give the profile a specific name.
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Put the profile at a specific point in its lifecycle.
     */
    public function withStatus(BalanceProfileStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Indicate that this is the configuration in play.
     */
    public function active(): static
    {
        return $this->withStatus(BalanceProfileStatus::Active);
    }

    /**
     * Indicate that the profile has been put away.
     */
    public function archived(): static
    {
        return $this->withStatus(BalanceProfileStatus::Archived);
    }

    /**
     * Attribute the profile to a specific account.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }
}
