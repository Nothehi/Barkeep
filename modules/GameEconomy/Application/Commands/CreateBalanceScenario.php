<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\BalanceScenarioData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Enums\BalanceScenarioStatus;
use Modules\GameEconomy\Domain\Events\BalanceScenarioCreated;
use Modules\GameEconomy\Domain\Exceptions\EconomyNameIsTaken;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Name a situation to read the economy under.
 *
 * The scenario is created with no overrides, because that is how one is built:
 * somebody names "Rich economy" and then works through the numbers one at a
 * time, comparing as they go. A create form that demanded the overrides up front
 * would require deciding the whole hypothetical before seeing any of it.
 */
final class CreateBalanceScenario
{
    public function __construct(
        private readonly EconomyRepository $economy,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(User $creator, BalanceProfile $profile, BalanceScenarioData $data): BalanceScenario
    {
        $this->guard->ensureProfileAcceptsConfiguration($profile);

        $name = $data->name ?? '';

        if ($this->economy->profileHasScenarioNamed($profile, $name)) {
            throw EconomyNameIsTaken::forScenario($name);
        }

        $scenario = new BalanceScenario;

        $scenario->fill([
            'name' => $name,
            'description' => $data->description,
        ]);

        $scenario->balance_profile_id = $profile->getKey();
        $scenario->status = BalanceScenarioStatus::default();
        $scenario->created_by = $creator->id;

        $scenario->save();

        $scenario->setRelation('profile', $profile);
        $scenario->setRelation('creator', $creator);

        event(new BalanceScenarioCreated(
            scenarioId: $scenario->id,
            profileId: $profile->getKey(),
            createdBy: $creator->id,
        ));

        return $scenario;
    }
}
