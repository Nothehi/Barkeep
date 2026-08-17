<?php

namespace Modules\DesignFramework\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories\ChecklistItemCompletionFactory;
use Modules\Identity\Domain\Models\User;

/**
 * A record that one game has met one checklist requirement.
 *
 * The framework defines the checklist; the game records its state. Same
 * separation as practices and criteria, and for the same reason: one published
 * checklist is read by every game following the version it belongs to.
 *
 * The row's existence is the tick. Unticking deletes it, which is what makes a
 * checklist item genuinely binary rather than a workflow with states.
 *
 * @property string $id
 * @property string $game_framework_id
 * @property string $checklist_item_id
 * @property string $completed_by
 * @property CarbonImmutable $completed_at
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read GameFramework|null $gameFramework
 * @property-read ChecklistItem|null $item
 * @property-read User|null $completer
 */
#[Fillable(['notes'])]
class ChecklistItemCompletion extends Model
{
    /** @use HasFactory<ChecklistItemCompletionFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'immutable_datetime',
        ];
    }

    /**
     * The adoption this tick belongs to.
     *
     * @return BelongsTo<GameFramework, $this>
     */
    public function gameFramework(): BelongsTo
    {
        return $this->belongsTo(GameFramework::class);
    }

    /**
     * The requirement that was met.
     *
     * @return BelongsTo<ChecklistItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class, 'checklist_item_id');
    }

    /**
     * The account that ticked it.
     *
     * @return BelongsTo<User, $this>
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ChecklistItemCompletionFactory
    {
        return ChecklistItemCompletionFactory::new();
    }
}
