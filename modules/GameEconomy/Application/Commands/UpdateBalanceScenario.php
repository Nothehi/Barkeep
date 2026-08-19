<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\BalanceScenarioData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Enums\BalanceScenarioStatus;
use Modules\GameEconomy\Domain\Exceptions\EconomyNameIsTaken;
use Modules\GameEconomy\Domain\Exceptions\InvalidBalanceScenarioTransition;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Rename a hypothetical, or say the studio is now reading against it.
 *
 * Activating is folded in here rather than given its own endpoint, which is the
 * opposite of the choice profiles make — and the reason is that it means
 * something different. A design state has one active profile, so activating one
 * retires another and is genuinely an action with consequences; any number of
 * scenarios may be active at once, so this is closer to a flag on the scenario
 * than to a lifecycle event.
 *
 * Archiving still has its own endpoint, because that one cannot be undone.
 */
final class UpdateBalanceScenario
{
    public function __construct(
        private readonly EconomyRepository $economy,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(
        User $actor,
        BalanceScenario $scenario,
        BalanceScenarioData $data,
        ?BalanceScenarioStatus $status = null,
    ): BalanceScenario {
        $this->guard->ensureScenarioIsModifiable($scenario);

        if ($data->name !== null && $data->name !== $scenario->name) {
            $profile = $scenario->profile;

            if ($profile !== null && $this->economy->profileHasScenarioNamed($profile, $data->name, $scenario->getKey())) {
                throw EconomyNameIsTaken::forScenario($data->name);
            }

            $scenario->name = $data->name;
        }

        if ($data->descriptionWasSent) {
            $scenario->description = $data->description;
        }

        if ($status !== null && $status !== $scenario->status) {
            if (! $scenario->status->canTransitionTo($status)) {
                throw InvalidBalanceScenarioTransition::between($scenario->status, $status);
            }

            $scenario->status = $status;
        }

        $scenario->save();

        return $scenario;
    }
}
