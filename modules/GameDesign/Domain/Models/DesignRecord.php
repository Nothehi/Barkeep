<?php

namespace Modules\GameDesign\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\GameDesign\Domain\Enums\Complexity;
use Modules\GameDesign\Domain\ValueObjects\PlayerCountRange;
use Modules\GameDesign\Domain\ValueObjects\PlayTimeRange;
use Modules\GameDesign\Infrastructure\Persistence\Eloquent\Factories\DesignRecordFactory;

/**
 * What has been decided about a game's design.
 *
 * The answers a methodology asks for, in a form the platform can read. Every
 * field here exists because something in the seeded framework already asked for
 * it — the player count, the playing time, the intended audience, the five parts
 * of the core loop — and asked for it as a checkbox the designer ticked on their
 * own word.
 *
 * ## Why this is not the game
 *
 * A game is identity and lifecycle: a name, an address, a workspace, a status.
 * This is the design. Keeping them apart is what the Game model's own docblock
 * asks for, and it buys two concrete things: a game that has decided nothing
 * carries no row at all rather than a dozen nulls, and the design can grow
 * fields without every query that touches a game paying for them.
 *
 * ## Why the fields are all nullable
 *
 * Because deciding is the work. A game in ideation has answered none of this,
 * and a default would be the platform answering on the designer's behalf — which
 * is worse than an empty field, because an empty field is honest about being
 * empty. "Not yet decided" is the most common and most useful state this record
 * is ever in, and the framework's job is to notice it rather than to hide it.
 *
 * ## The intended design, not the observed one
 *
 * Every figure here is a constraint the designer chose. When playtesting can say
 * how long the game actually runs, that is a different number worth showing
 * beside this one rather than overwriting it — an intention and a measurement
 * disagreeing is information, and a single field that quietly became a
 * measurement would destroy it.
 *
 * @property string $id
 * @property string $game_id
 * @property string|null $pitch
 * @property int|null $player_count_min
 * @property int|null $player_count_max
 * @property int|null $play_time_min
 * @property int|null $play_time_max
 * @property int|null $target_age_min
 * @property Complexity|null $complexity
 * @property string|null $audience
 * @property string|null $core_action
 * @property string|null $core_cost
 * @property string|null $core_reward
 * @property string|null $win_condition
 * @property string|null $failure_condition
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Game|null $game
 * @property-read Collection<int, Mechanic> $mechanics
 */
#[Fillable([
    'pitch',
    'player_count_min',
    'player_count_max',
    'play_time_min',
    'play_time_max',
    'target_age_min',
    'audience',
    'core_action',
    'core_cost',
    'core_reward',
    'win_condition',
    'failure_condition',
])]
class DesignRecord extends Model
{
    /** @use HasFactory<DesignRecordFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'player_count_min' => 'integer',
            'player_count_max' => 'integer',
            'play_time_min' => 'integer',
            'play_time_max' => 'integer',
            'target_age_min' => 'integer',
            'complexity' => Complexity::class,
        ];
    }

    /**
     * The game this describes.
     *
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * The terms from the shared vocabulary this game claims.
     *
     * @return BelongsToMany<Mechanic, $this>
     */
    public function mechanics(): BelongsToMany
    {
        return $this->belongsToMany(Mechanic::class, 'design_record_mechanic')
            ->withTimestamps()
            ->orderBy('mechanics.name');
    }

    /**
     * The player count as a value object, when it has been decided.
     */
    public function playerCount(): ?PlayerCountRange
    {
        return PlayerCountRange::fromNullableInts($this->player_count_min, $this->player_count_max);
    }

    /**
     * The playing time as a value object, when it has been decided.
     */
    public function playTime(): ?PlayTimeRange
    {
        return PlayTimeRange::fromNullableMinutes($this->play_time_min, $this->play_time_max);
    }

    /**
     * Determine whether the core loop has been written down in full.
     *
     * All five parts, because a loop missing its cost or its failure condition
     * is not a loop somebody has finished thinking about — which is exactly what
     * the framework's core loop checklist is asking.
     */
    public function hasCompleteCoreLoop(): bool
    {
        foreach (['core_action', 'core_cost', 'core_reward', 'win_condition', 'failure_condition'] as $part) {
            if ($this->isBlank($part)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the named field holds an answer.
     *
     * Whitespace is not an answer. A field containing a space would otherwise
     * satisfy a framework criterion that asked whether the question had been
     * answered, which is the one thing that must not be possible.
     */
    public function isBlank(string $field): bool
    {
        $value = $this->getAttribute($field);

        if ($value === null) {
            return true;
        }

        return is_string($value) && trim($value) === '';
    }

    /**
     * Determine whether anything at all has been decided.
     *
     * Used to tell "no record yet" from "a record that says nothing", which
     * look the same to a designer and should.
     */
    public function isEmpty(): bool
    {
        return $this->playerCount() === null
            && $this->playTime() === null
            && $this->complexity === null
            && $this->target_age_min === null
            && $this->isBlank('pitch')
            && $this->isBlank('audience')
            && $this->isBlank('core_action')
            && $this->isBlank('core_cost')
            && $this->isBlank('core_reward')
            && $this->isBlank('win_condition')
            && $this->isBlank('failure_condition')
            && $this->mechanics->isEmpty();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DesignRecordFactory
    {
        return DesignRecordFactory::new();
    }
}
