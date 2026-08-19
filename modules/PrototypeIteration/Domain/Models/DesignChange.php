<?php

namespace Modules\PrototypeIteration\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Enums\DesignChangeCategory;
use Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories\DesignChangeFactory;

/**
 * One deliberate modification made during an iteration.
 *
 * "Reduced starting resources from five to three." "Removed the trading
 * phase." "Changed the victory condition from ten points to twelve." Each is a
 * single edit somebody chose to make, recorded at the size a designer would
 * describe it.
 *
 * The field that makes this worth storing is the reason. A list of edits is a
 * changelog and it answers "what is different"; a list of edits with reasons is
 * a design rationale and it answers "why is the game like this" — which is the
 * question somebody actually has eighteen months later when they are wondering
 * whether the trading phase should come back. So the reason is required and the
 * description is not: what changed is usually obvious from the title, and why
 * never is.
 *
 * Changes are not diffs. Nothing here computes the difference between two
 * versions of a rulebook, and nothing should: the designer's own sentence about
 * what they changed is more useful than a mechanical comparison, because it
 * carries intent.
 *
 * @property string $id
 * @property string $iteration_id
 * @property DesignChangeCategory $category
 * @property string $title
 * @property string|null $description
 * @property string $reason
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Iteration|null $iteration
 * @property-read User|null $creator
 */
#[Fillable(['title', 'description', 'reason'])]
class DesignChange extends Model
{
    /** @use HasFactory<DesignChangeFactory> */
    use HasFactory, HasUuids;

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'category' => DesignChangeCategory::Other->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DesignChangeCategory::class,
        ];
    }

    /**
     * The cycle this change was made during.
     *
     * @return BelongsTo<Iteration, $this>
     */
    public function iteration(): BelongsTo
    {
        return $this->belongsTo(Iteration::class);
    }

    /**
     * The account that made it.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The game this belongs to, read through the cycle that owns it.
     *
     * The game is not a column here on purpose. A change, an experiment and a decision
     * all belong to exactly one iteration, and the iteration already knows its game — a
     * second copy would be a second answer that could disagree with the first after a
     * badly written import.
     *
     * The events this module dispatches carry the game id all the same, because a
     * consumer should not have to join back through two tables to find out which project
     * an event was about. This is where they get it: from the relation when it is loaded,
     * and from a single scalar read when it is not.
     */
    public function gameId(): string
    {
        return (string) $this->iteration->game_id;
    }

    /**
     * Determine whether the change belongs to the given iteration.
     */
    public function belongsToIteration(Iteration $iteration): bool
    {
        return $this->iteration_id === $iteration->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DesignChangeFactory
    {
        return DesignChangeFactory::new();
    }
}
