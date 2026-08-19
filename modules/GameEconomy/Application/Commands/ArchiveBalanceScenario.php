<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Enums\BalanceScenarioStatus;
use Modules\GameEconomy\Domain\Events\BalanceScenarioArchived;
use Modules\GameEconomy\Domain\Exceptions\InvalidBalanceScenarioTransition;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\Identity\Domain\Models\User;

/**
 * Put a hypothetical away.
 *
 * Its overrides survive, and stay readable. "What did we think four-player
 * looked like before we changed the starting gold?" is a question worth being
 * able to answer, and deleting the scenario would remove the only record of it.
 */
final class ArchiveBalanceScenario
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, BalanceScenario $scenario): BalanceScenario
    {
        $this->guard->ensureScenarioIsModifiable($scenario);

        if (! $scenario->status->canTransitionTo(BalanceScenarioStatus::Archived)) {
            throw InvalidBalanceScenarioTransition::between($scenario->status, BalanceScenarioStatus::Archived);
        }

        $scenario->status = BalanceScenarioStatus::Archived;
        $scenario->save();

        event(new BalanceScenarioArchived(
            scenarioId: $scenario->getKey(),
            profileId: $scenario->balance_profile_id,
            archivedBy: $actor->id,
        ));

        return $scenario;
    }
}
