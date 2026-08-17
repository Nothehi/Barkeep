<?php

namespace Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\ChecklistItemCompletion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\Identity\Domain\Models\User;

/**
 * A record that one game met one checklist requirement.
 *
 * @extends Factory<ChecklistItemCompletion>
 */
class ChecklistItemCompletionFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<ChecklistItemCompletion>
     */
    protected $model = ChecklistItemCompletion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_framework_id' => GameFramework::factory(),
            'checklist_item_id' => ChecklistItem::factory(),
            'notes' => null,
            'completed_by' => User::factory(),
            'completed_at' => now(),
        ];
    }

    /**
     * Record this against a specific adoption and a specific piece of content.
     *
     * The pair the module actually guarantees, so this is the state almost every test wants:
     * it keeps the record pointing at content the game's own version defines.
     */
    public function of(GameFramework $adoption, ChecklistItem $item): static
    {
        return $this->state(fn (array $attributes) => [
            'game_framework_id' => $adoption->id,
            'checklist_item_id' => $item->id,
            'completed_by' => $adoption->adopted_by,
        ]);
    }

    /**
     * Attribute the record to a specific account.
     */
    public function by(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_by' => $user->id,
        ]);
    }
}
