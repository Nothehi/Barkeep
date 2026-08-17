<?php

namespace Modules\DesignFramework\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\DesignFramework\Application\Services\GameFrameworkGuard;
use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;
use Modules\DesignFramework\Domain\Exceptions\InvalidGameFrameworkTransition;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\Identity\Domain\Models\User;

/**
 * Pick a paused methodology back up.
 *
 * The counterpart to {@see PauseGameFramework}, and the reason pausing is not a one-way
 * door. `GameFrameworkStatus` allows Paused → Active, so leaving the move unreachable
 * would make a paused adoption a state a studio could enter and never leave except by
 * declaring itself finished with a framework it had barely started.
 *
 * Nothing is restored, because nothing was taken away: pausing only refused new writes.
 * Everything the studio recorded is exactly where they left it.
 */
final class ResumeGameFramework
{
    public function __construct(private readonly GameFrameworkGuard $guard) {}

    public function handle(User $actor, GameFramework $adoption): GameFramework
    {
        $this->guard->ensureAdoptionIsOpen($adoption);

        DB::transaction(function () use ($adoption): void {
            $fresh = GameFramework::query()
                ->whereKey($adoption->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(GameFrameworkStatus::Active)) {
                throw InvalidGameFrameworkTransition::between($fresh->status, GameFrameworkStatus::Active);
            }

            $fresh->forceFill(['status' => GameFrameworkStatus::Active])->save();

            $adoption->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        return $adoption;
    }
}
