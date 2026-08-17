<?php

namespace Modules\DesignFramework\Application\Commands;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\DesignFramework\Application\Services\FrameworkModificationGuard;
use Modules\DesignFramework\Application\Services\GameFrameworkGuard;
use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;
use Modules\DesignFramework\Domain\Events\GameFrameworkAssigned;
use Modules\DesignFramework\Domain\Exceptions\GameAlreadyFollowsAFramework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\GameFrameworkRepository;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;

/**
 * Point a game at a methodology.
 *
 * The operation that joins the two halves of the product, and the one place the
 * historical relationship is established. Three rules, and each of them is here rather
 * than in a controller because each is a statement about the domain:
 *
 * 1. **The version must be adoptable.** Published, inside a published framework. A draft
 *    would change its questions underneath the game; an archived one is retired, and a
 *    new project starting on it is a mistake worth refusing rather than allowing
 *    quietly.
 * 2. **The game must be open.** An archived game, or one in a closed workspace, records
 *    nothing new.
 * 3. **One framework per game.** Enforced here *and* by a unique index, because the
 *    check and the insert are not atomic. What this deliberately is not is migration:
 *    moving a game from v1 to v2 has real decisions in it about what happens to
 *    evaluations already recorded, and silently reassigning here would be that
 *    operation done badly.
 *
 * The version captured is the version forever. When v2 is published this game keeps
 * reading v1 — its evaluations point at v1's criteria, and that is what makes them mean
 * anything. Nothing in this module ever rewrites `framework_version_id`.
 *
 * The adopter is the signed in account rather than anything the caller sent. Every field
 * in this module that identifies a person comes from the request context, never from its
 * body.
 */
final class AssignFrameworkToGame
{
    public function __construct(
        private readonly GameFrameworkGuard $games,
        private readonly FrameworkModificationGuard $frameworks,
        private readonly GameFrameworkRepository $adoptions,
    ) {}

    public function handle(User $adopter, Game $game, FrameworkVersion $version): GameFramework
    {
        $this->games->ensureGameAcceptsFramework($game);
        $this->frameworks->ensureVersionIsAdoptable($version);

        if ($this->adoptions->gameHasFramework($game)) {
            throw GameAlreadyFollowsAFramework::forGame((string) $game->getKey());
        }

        $startedAt = now()->toImmutable();

        $adoption = new GameFramework;

        $adoption->game_id = $game->getKey();
        $adoption->framework_version_id = $version->getKey();
        $adoption->status = GameFrameworkStatus::default();
        $adoption->started_at = $startedAt;
        $adoption->adopted_by = $adopter->id;

        try {
            $adoption->save();
        } catch (UniqueConstraintViolationException) {
            /*
             * Somebody adopted a framework for this game between the check above and the
             * insert. The index is what actually holds the rule; this turns its refusal
             * into the module's own message rather than a 500.
             */
            throw GameAlreadyFollowsAFramework::forGame((string) $game->getKey());
        }

        /*
         * Hand back the objects already in hand rather than letting the caller lazily
         * reload them. The game in particular carries a memoised workspace membership,
         * which every permission answer on the way out is about to need.
         */
        $adoption->setRelation('game', $game);
        $adoption->setRelation('version', $version);
        $adoption->setRelation('adopter', $adopter);

        event(new GameFrameworkAssigned(
            gameFrameworkId: $adoption->id,
            gameId: $game->getKey(),
            frameworkId: $version->framework_id,
            frameworkVersionId: $version->getKey(),
            versionNumber: $version->version_number,
            adoptedBy: $adopter->id,
            startedAt: $startedAt->toDateTimeImmutable(),
        ));

        return $adoption;
    }
}
