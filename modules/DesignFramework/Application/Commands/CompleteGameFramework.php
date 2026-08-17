<?php

namespace Modules\DesignFramework\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\DesignFramework\Application\Services\GameFrameworkGuard;
use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;
use Modules\DesignFramework\Domain\Exceptions\InvalidGameFrameworkTransition;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\Identity\Domain\Models\User;

/**
 * Declare a game finished with its methodology.
 *
 * Explicit, always. An adoption does not complete itself when the last checklist item is
 * ticked, because "everything countable is done" and "we have finished working this way"
 * are different claims and only the designer can make the second. Plenty of studios stop
 * at eighty per cent because the last twenty was about a production run they are not
 * doing; plenty keep the framework open long after the arithmetic says complete.
 *
 * Notice what is *not* required: that progress has reached a hundred per cent. Refusing
 * to let a designer close a framework they have decided is done would be this module
 * insisting it knows better than the person doing the work — and the progress figure
 * exists to inform that decision, not to gate it.
 *
 * Terminal. A studio that finishes a methodology and later wants back in is starting
 * again, which is `AssignFrameworkToGame` on the version they mean rather than a quiet
 * reversal of a declaration they made. That is also why the completed record keeps every
 * evaluation and answer: it is the account of how the design was worked.
 */
final class CompleteGameFramework
{
    public function __construct(private readonly GameFrameworkGuard $guard) {}

    public function handle(User $actor, GameFramework $adoption): GameFramework
    {
        $this->guard->ensureAdoptionIsOpen($adoption);

        $completedAt = now()->toImmutable();

        DB::transaction(function () use ($adoption, $completedAt): void {
            $fresh = GameFramework::query()
                ->whereKey($adoption->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(GameFrameworkStatus::Completed)) {
                throw InvalidGameFrameworkTransition::between($fresh->status, GameFrameworkStatus::Completed);
            }

            $fresh->forceFill([
                'status' => GameFrameworkStatus::Completed,
                'completed_at' => $completedAt,
            ])->save();

            $adoption->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        return $adoption;
    }
}
