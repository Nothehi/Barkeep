<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when a configuration becomes the one in play.
 *
 * Carries the profile it displaced, where there was one. That field is the
 * interesting half: "the studio switched from one tuning to another" is a
 * different event from "the studio had no active configuration and now does",
 * and a consumer reconstructing a timeline cannot tell them apart afterwards.
 */
final readonly class BalanceProfileActivated
{
    public function __construct(
        public string $profileId,
        public string $gameVersionId,
        public ?string $supersededProfileId,
        public string $activatedBy,
    ) {}
}
