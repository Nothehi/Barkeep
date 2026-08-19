<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\BalanceAssumptionData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Models\BalanceAssumption;
use Modules\Identity\Domain\Models\User;

/**
 * Revise a belief, or change how strongly it is held.
 *
 * Raising and lowering confidence is the edit this command exists for. An
 * assumption written as a hunch and later confirmed at four tables should be
 * able to say so, and the alternative — a new assumption superseding the old —
 * would leave the list full of the same sentence at three confidences.
 */
final class UpdateBalanceAssumption
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, BalanceAssumption $assumption, BalanceAssumptionData $data): BalanceAssumption
    {
        $profile = $assumption->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        if ($data->title !== null) {
            $assumption->title = $data->title;
        }

        if ($data->sent('description')) {
            $assumption->description = $data->description;
        }

        if ($data->category !== null) {
            $assumption->category = $data->category;
        }

        if ($data->confidence !== null) {
            $assumption->confidence = $data->confidence;
        }

        $assumption->save();

        return $assumption;
    }
}
