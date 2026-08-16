<?php

namespace Modules\GameDesign\Application\Commands;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\GameDesign\Application\DTOs\CreateGameData;
use Modules\GameDesign\Application\Services\GameModificationGuard;
use Modules\GameDesign\Application\Services\GameSlugGenerator;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Events\GameCreated;
use Modules\GameDesign\Domain\Exceptions\InvalidGameSlug;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Start a new game project inside a workspace.
 *
 * The workspace is passed in as a resolved model rather than as an id: the
 * caller has already proved which workspace it means, so there is nothing
 * here that a request body could redirect.
 *
 * A game begins as a draft in the idea phase. Those are two separate
 * defaults, because they answer two separate questions — nobody is working on
 * it yet, and there is nothing designed yet — and they move independently
 * from here on.
 *
 * No transaction: a game is one row, and one insert is already atomic. The
 * unique index on (workspace_id, slug) is what settles a race, and it is
 * caught below.
 */
final class CreateGame
{
    public function __construct(
        private readonly GameSlugGenerator $slugs,
        private readonly GameModificationGuard $guard,
    ) {}

    public function handle(User $creator, Workspace $workspace, CreateGameData $data): Game
    {
        $this->guard->ensureWorkspaceIsModifiable($workspace);

        $slug = $data->slug ?? $this->slugs->forName($workspace, $data->name);

        $game = new Game;

        $game->fill([
            'name' => $data->name,
            'slug' => $slug->value,
            'description' => $data->description,
        ]);

        /**
         * Set outside the fill. Ownership and authorship are not mass
         * assignable attributes, precisely so that no request can name the
         * workspace a game belongs to or the account it is credited to.
         */
        $game->workspace_id = $workspace->getKey();
        $game->created_by = $creator->id;
        $game->status = GameStatus::default();
        $game->design_phase = $data->designPhaseOrDefault();

        try {
            $game->save();
        } catch (UniqueConstraintViolationException) {
            /**
             * Two people claimed the same address in this workspace between
             * validation and the insert. The database settled it; report the
             * loser's address back rather than renaming their game behind
             * their back.
             */
            throw InvalidGameSlug::taken($slug->value);
        }

        /**
         * Handed the workspace it was created in, so anything rendering the
         * new game resolves permissions against the instance already in
         * memory rather than reading the workspace back.
         */
        $game->setRelation('workspace', $workspace);

        event(new GameCreated(
            gameId: $game->id,
            workspaceId: $workspace->getKey(),
            createdBy: $creator->id,
            slug: $game->slug,
            createdAt: $game->created_at ?? now(),
        ));

        return $game;
    }
}
