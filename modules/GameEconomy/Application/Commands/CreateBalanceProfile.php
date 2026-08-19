<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\DTOs\BalanceProfileData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Enums\BalanceProfileStatus;
use Modules\GameEconomy\Domain\Events\BalanceProfileCreated;
use Modules\GameEconomy\Domain\Exceptions\EconomyNameIsTaken;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Infrastructure\GameDesign\GameCatalogue;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\BalanceProfileRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Start configuring the economy of one design state.
 *
 * The module's central invariant is established here: the game arrives as a
 * resolved route binding, the design version arrives as the segment after it,
 * and the version is proved to be one of that game's own. A version belonging to
 * somebody else's game is not compared and rejected — it does not resolve.
 *
 * The creator is the signed in account rather than anything the caller sent.
 * Every field in this module that identifies a person or a boundary comes from
 * the request context, never from its body.
 *
 * A profile starts as a draft and empty. It is not seeded with a starter set of
 * resources, and that was a deliberate call: a platform that created "Gold,
 * Wood, Stone" for every new configuration would be telling every studio what
 * kind of game they are making, and section 7 of the brief rules it out.
 */
final class CreateBalanceProfile
{
    public function __construct(
        private readonly GameCatalogue $catalogue,
        private readonly BalanceProfileRepository $profiles,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(User $creator, Game $game, GameVersion $version, BalanceProfileData $data): BalanceProfile
    {
        $this->guard->ensureGameAcceptsBalanceWork($game);

        $version = $this->catalogue->versionOf($game, $version->getKey());

        $name = $data->name ?? '';

        if ($this->profiles->versionHasProfileNamed($version, $name)) {
            throw EconomyNameIsTaken::forProfile($name);
        }

        $profile = new BalanceProfile;

        $profile->fill([
            'name' => $name,
            'description' => $data->description,
        ]);

        $profile->game_version_id = $version->getKey();
        $profile->status = BalanceProfileStatus::default();
        $profile->created_by = $creator->id;

        $profile->save();

        /*
         * Hand back the objects already in hand rather than letting the caller
         * lazily reload them. The version in particular carries the game, whose
         * memoised workspace membership every permission answer on the way out
         * is about to need.
         */
        $profile->setRelation('version', $version);
        $profile->setRelation('creator', $creator);

        event(new BalanceProfileCreated(
            profileId: $profile->id,
            gameVersionId: $version->getKey(),
            createdBy: $creator->id,
        ));

        return $profile;
    }
}
