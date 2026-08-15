<?php

namespace Modules\GameDesign\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GameDesign\Domain\ValueObjects\VersionNumber;
use Modules\GameDesign\Infrastructure\Persistence\Eloquent\Factories\GameVersionFactory;
use Modules\Identity\Domain\Models\User;

/**
 * One recorded iteration of a game.
 *
 * A design does not improve continuously; it improves in jumps, and designers
 * talk about those jumps by number — "that broke in v3", "v5 is the one we
 * took to the convention". This model exists to give those numbers a home
 * before there is anything substantial to hang off them.
 *
 * It is not document versioning. A version records that an iteration existed,
 * who cut it and what changed in prose. When design documents arrive they
 * will point at a version; the version will not grow to contain them.
 *
 * @property string $id
 * @property string $game_id
 * @property int $version_number
 * @property string|null $name
 * @property string|null $description
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Game|null $game
 * @property-read User|null $creator
 */
#[Fillable(['name', 'description'])]
class GameVersion extends Model
{
    /** @use HasFactory<GameVersionFactory> */
    use HasFactory, HasUuids;

    /**
     * The route key used in human facing URLs.
     *
     * Versions are addressed by their number, resolved through the bound
     * game. A number is only meaningful inside one game, so — as with game
     * addresses — every version route is nested and scoped.
     */
    public function getRouteKeyName(): string
    {
        return 'version_number';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
        ];
    }

    /**
     * The game this is an iteration of.
     *
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * The account that cut the version.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the version's ordinal as a value object.
     */
    public function number(): VersionNumber
    {
        return VersionNumber::fromInt($this->version_number);
    }

    /**
     * How the version is written wherever people read it: v1, v2, v3.
     */
    public function label(): string
    {
        return $this->number()->label();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): GameVersionFactory
    {
        return GameVersionFactory::new();
    }
}
