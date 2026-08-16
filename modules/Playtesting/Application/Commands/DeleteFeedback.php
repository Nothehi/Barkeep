<?php

namespace Modules\Playtesting\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * Withdraw a piece of feedback while the session is still open.
 *
 * Bounded exactly as observations are, and for a stronger reason: feedback is
 * somebody else's words. Once the session has ended, what a participant said
 * about a designer's game stops being something the designer can remove.
 */
final class DeleteFeedback
{
    public function __construct(private readonly PlaytestModificationGuard $guard) {}

    public function handle(User $actor, PlaytestSession $session, PlaytestFeedback $feedback): void
    {
        $this->guard->ensureSessionAcceptsEvidence($session);

        $feedback->delete();
    }
}
