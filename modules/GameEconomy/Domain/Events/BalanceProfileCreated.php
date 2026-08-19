<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when a studio starts configuring the economy of a design state.
 *
 * The design version travels with it because it is what makes the event
 * placeable. Anything that eventually reasons about a studio's pace needs to
 * know which iteration of the game an economy was built for without going back
 * to the table to find out.
 *
 * These events are the module's published surface for everything built on top of
 * it — analytics counting profiles, gamification noticing a milestone, AI
 * suggesting a change. No consumer is implemented here, and section 49 is
 * explicit that none should be yet: an event with no listener costs nothing, and
 * a listener written before its module exists is a guess.
 */
final readonly class BalanceProfileCreated
{
    public function __construct(
        public string $profileId,
        public string $gameVersionId,
        public string $createdBy,
    ) {}
}
