<?php

namespace Modules\GameDesign\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\ValueObjects\GameSlug;
use Modules\GameDesign\Infrastructure\Persistence\Eloquent\Factories\GameFactory;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * A board-game design project.
 *
 * The aggregate the whole product is about. It is deliberately small: a name,
 * an address, where the project is and where the design is. Everything a
 * designer will eventually want to record — mechanics, components, rules,
 * player experience — belongs to capabilities that do not exist yet, and
 * putting placeholder columns here now would fix decisions nobody has made.
 *
 * A game belongs to exactly one workspace and never moves. That is the
 * security boundary: every read is scoped to a workspace and every write is
 * authorized against the workspace the game actually belongs to, never
 * against one named in a request.
 *
 * The game knows its workspace and its creator by id only. Who those are is
 * Workspace's and Identity's business.
 *
 * @property string $id
 * @property string $workspace_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property GameStatus $status
 * @property DesignPhase $design_phase
 * @property string $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Workspace|null $workspace
 * @property-read User|null $creator
 * @property-read Collection<int, GameVersion> $versions
 * @property-read GameVersion|null $latestVersion
 * @property-read int|null $versions_count
 */
#[Fillable(['name', 'slug', 'description'])]
class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory, HasUuids;

    /**
     * The route key used in human facing URLs.
     *
     * Games are addressed by slug, resolved through the bound workspace's own
     * games. Because addresses are only unique per workspace, this key is
     * meaningless without that scoping — which is why every game route is
     * nested and declares `scopeBindings()`.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The model's default attribute values.
     *
     * Set here as well as on the columns so a freshly built game reports its
     * state before being reloaded from the database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => GameStatus::Draft->value,
        'design_phase' => DesignPhase::Idea->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GameStatus::class,
            'design_phase' => DesignPhase::class,
        ];
    }

    /**
     * The workspace the game belongs to.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * The account that started the project.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Every iteration of the game, oldest first.
     *
     * @return HasMany<GameVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(GameVersion::class);
    }

    /**
     * The game's current iteration.
     *
     * Ordered by version number rather than by creation time: the number is
     * the thing the domain guarantees is unique and ordered, and it is what a
     * designer means by "the latest one".
     *
     * @return HasOne<GameVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(GameVersion::class)->ofMany('version_number', 'max');
    }

    /**
     * Get the game's address as a value object.
     */
    public function slug(): GameSlug
    {
        return GameSlug::fromString($this->slug);
    }

    /**
     * Determine whether the game itself may still be changed.
     *
     * Only answers for the game. Whether the workspace around it is still
     * accepting changes is a separate question, and both have to be true —
     * see the policy and the commands, which check each in turn.
     */
    public function isModifiable(): bool
    {
        return $this->status->allowsModification();
    }

    /**
     * Determine whether the game has been put away.
     */
    public function isArchived(): bool
    {
        return $this->status === GameStatus::Archived;
    }

    /**
     * Determine whether the game belongs to the given workspace.
     *
     * Used where a workspace has been resolved separately from the game, so
     * that the two are proved to match rather than assumed to.
     */
    public function belongsToWorkspace(Workspace $workspace): bool
    {
        return $this->workspace_id === $workspace->getKey();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): GameFactory
    {
        return GameFactory::new();
    }
}
