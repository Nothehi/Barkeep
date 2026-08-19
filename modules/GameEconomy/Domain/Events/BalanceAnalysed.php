<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when a configuration is read for findings.
 *
 * The odd one out in this module's events: nothing changed. It fires because
 * *the act of looking* is the interesting fact for anything that eventually
 * reasons about how a studio works — whether a team analyses before every
 * playtest or only after something goes wrong is a question about their process,
 * and it cannot be reconstructed from the data afterwards.
 *
 * The counts travel because they are what a consumer would otherwise recompute,
 * and recomputing would give a different answer the moment the configuration
 * moved on.
 */
final readonly class BalanceAnalysed
{
    public function __construct(
        public string $profileId,
        public int $warnings,
        public int $errors,
    ) {}
}
