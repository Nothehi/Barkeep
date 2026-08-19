<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when a configuration is put away for good.
 *
 * Terminal: nothing un-archives a profile, so a consumer may treat this as the
 * end of that configuration's life rather than as one more state change.
 */
final readonly class BalanceProfileArchived
{
    public function __construct(
        public string $profileId,
        public string $gameVersionId,
        public string $archivedBy,
    ) {}
}
