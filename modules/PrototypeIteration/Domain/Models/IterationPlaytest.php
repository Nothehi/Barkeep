<?php

namespace Modules\PrototypeIteration\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Infrastructure\Persistence\Eloquent\Factories\IterationPlaytestFactory;

/**
 * The record that an iteration was tested through a particular playtest.
 *
 * A join row and nothing more, and the restraint is the design. There is no
 * copy of the playtest's title here, no cached session count, no denormalised
 * observation total — because Playtesting owns the evidence, and a second copy
 * of it in this module would be a second answer waiting to disagree with the
 * first. Everything an iteration screen shows about an attached playtest is
 * read back through Playtesting's own contract at render time.
 *
 * There is deliberately no `playtest()` relation. Declaring one would put
 * Playtesting's model in reach of every caller that holds a link, and the whole
 * arrangement depends on exactly one file in this module knowing that
 * Playtesting exists — see `PlaytestEvidence`, which resolves ids into this
 * module's own value objects. An architecture test holds that line.
 *
 * A playtest may be attached to several iterations and an iteration to several
 * playtests; what is refused is attaching the same pair twice, which says
 * nothing the first attachment did not.
 *
 * @property string $id
 * @property string $iteration_id
 * @property string $playtest_id
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Iteration|null $iteration
 * @property-read User|null $creator
 */
class IterationPlaytest extends Model
{
    /** @use HasFactory<IterationPlaytestFactory> */
    use HasFactory, HasUuids;

    /**
     * The cycle the evidence was gathered for.
     *
     * @return BelongsTo<Iteration, $this>
     */
    public function iteration(): BelongsTo
    {
        return $this->belongsTo(Iteration::class);
    }

    /**
     * The account that made the connection.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determine whether the link belongs to the given iteration.
     */
    public function belongsToIteration(Iteration $iteration): bool
    {
        return $this->iteration_id === $iteration->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): IterationPlaytestFactory
    {
        return IterationPlaytestFactory::new();
    }
}
