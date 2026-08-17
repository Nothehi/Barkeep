<?php

namespace Modules\DesignFramework\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\DesignFramework\Application\Services\GameFrameworkGuard;
use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;
use Modules\DesignFramework\Domain\Exceptions\InvalidGameFrameworkTransition;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\Identity\Domain\Models\User;

/**
 * Step away from a methodology for a while.
 *
 * Pausing exists so that stopping can be honest. Without it, the only ways to step away
 * are to leave the adoption looking active — which makes every progress bar a claim
 * nobody is working on — or to complete it, which asserts something that did not happen.
 *
 * A paused adoption refuses new evaluations, completions and answers, and keeps every
 * one it already has. Reading is entirely unaffected: the phases, the criteria and the
 * progress are all still there when the studio comes back.
 *
 * The move is decided under a row lock and against the status read inside it, so pausing
 * and completing at the same moment produce one winner and one honest refusal.
 */
final class PauseGameFramework
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

            if (! $fresh->status->canTransitionTo(GameFrameworkStatus::Paused)) {
                throw InvalidGameFrameworkTransition::between($fresh->status, GameFrameworkStatus::Paused);
            }

            $fresh->forceFill(['status' => GameFrameworkStatus::Paused])->save();

            $adoption->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        return $adoption;
    }
}
