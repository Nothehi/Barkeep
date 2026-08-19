<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\BalanceAssumptionData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Enums\AssumptionCategory;
use Modules\GameEconomy\Domain\Enums\AssumptionConfidence;
use Modules\GameEconomy\Domain\Models\BalanceAssumption;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\Identity\Domain\Models\User;

/**
 * Write down why a number is what it is.
 *
 * There is no event for this, unlike almost everything else in the module, and
 * the absence is deliberate rather than an oversight. An assumption is a note to
 * the studio's future self; nothing outside GameEconomy has any business
 * reacting to somebody writing one down, and publishing it would invite a
 * consumer to start scoring teams on how much they document.
 */
final class CreateBalanceAssumption
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $creator, BalanceProfile $profile, BalanceAssumptionData $data): BalanceAssumption
    {
        $this->guard->ensureProfileAcceptsConfiguration($profile);

        $assumption = new BalanceAssumption;

        $assumption->fill([
            'title' => $data->title ?? '',
            'description' => $data->description,
        ]);

        $assumption->balance_profile_id = $profile->getKey();
        $assumption->category = $data->category ?? AssumptionCategory::default();
        $assumption->confidence = $data->confidence ?? AssumptionConfidence::default();
        $assumption->created_by = $creator->id;

        $assumption->save();

        $assumption->setRelation('profile', $profile);
        $assumption->setRelation('creator', $creator);

        return $assumption;
    }
}
