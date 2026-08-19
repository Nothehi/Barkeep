<?php

namespace Modules\GameEconomy\Application\Queries;

use Modules\GameEconomy\Application\Commands\AnalyseBalanceProfile;
use Modules\GameEconomy\Application\DTOs\BalanceAnalysis;
use Modules\GameEconomy\Domain\Models\BalanceProfile;

/**
 * Read a configuration's findings without announcing that anybody looked.
 *
 * The silent twin of {@see AnalyseBalanceProfile}, and the split is the only
 * reason both exist. Analysis writes nothing either way — section 31 — but it
 * does publish an event, and a dashboard that recomputes the summary on every
 * page load would otherwise fill a studio's history with the fact that somebody
 * refreshed a screen.
 *
 * So: this is what screens call, and the command is what an explicit "analyse"
 * action calls.
 */
final class GetBalanceAnalysis
{
    public function __construct(private readonly AnalyseBalanceProfile $analyse) {}

    public function handle(BalanceProfile $profile): BalanceAnalysis
    {
        return $this->analyse->handle($profile, announce: false);
    }
}
