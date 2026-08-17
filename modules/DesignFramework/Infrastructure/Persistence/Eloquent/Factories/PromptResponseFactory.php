<?php

namespace Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Domain\Models\PromptResponse;
use Modules\Identity\Domain\Models\User;

/**
 * What one game's designers wrote in answer to one of the framework's questions.
 *
 * @extends Factory<PromptResponse>
 */
class PromptResponseFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<PromptResponse>
     */
    protected $model = PromptResponse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_framework_id' => GameFramework::factory(),
            'prompt_id' => DesignPrompt::factory(),
            'response' => fake()->paragraph(),
            'answered_by' => User::factory(),
            'answered_at' => now(),
        ];
    }

    /**
     * Record this against a specific adoption and a specific piece of content.
     *
     * The pair the module actually guarantees, so this is the state almost every test wants:
     * it keeps the record pointing at content the game's own version defines.
     */
    public function of(GameFramework $adoption, DesignPrompt $prompt): static
    {
        return $this->state(fn (array $attributes) => [
            'game_framework_id' => $adoption->id,
            'prompt_id' => $prompt->id,
            'answered_by' => $adoption->adopted_by,
        ]);
    }

    /**
     * Attribute the record to a specific account.
     */
    public function by(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'answered_by' => $user->id,
        ]);
    }
}
