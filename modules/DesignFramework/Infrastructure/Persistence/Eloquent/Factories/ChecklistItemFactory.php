<?php

namespace Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;

/**
 * @extends Factory<ChecklistItem>
 */
class ChecklistItemFactory extends Factory
{
    /**
     * The model the factory builds.
     *
     * @var class-string<ChecklistItem>
     */
    protected $model = ChecklistItem::class;

    /**
     * Define the model's default state.
     *
     * Required by default, matching the column and the command. A test about optional items
     * says {@see optional()}, which is also the shape the progress assertions want: an item
     * that does not count has to be asked for.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = ucfirst(str_replace('-', ' ', fake()->unique()->slug(3)));

        return [
            'checklist_id' => Checklist::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => null,
            'position' => 1,
            'required' => true,
        ];
    }

    /**
     * Append the item to a specific list.
     */
    public function inChecklist(Checklist $checklist): static
    {
        return $this->state(fn (array $attributes) => [
            'checklist_id' => $checklist->id,
            'position' => $checklist->items()->count() + 1,
        ]);
    }

    /**
     * Give the item a specific title, deriving its address to match.
     */
    public function titled(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
            'slug' => Str::slug($title),
        ]);
    }

    /**
     * Place the item at a specific point on the list.
     */
    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }

    /**
     * Indicate that the item is a suggestion rather than a requirement.
     *
     * An optional item is shown and can be ticked, and does not count towards progress.
     */
    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'required' => false,
        ]);
    }
}
