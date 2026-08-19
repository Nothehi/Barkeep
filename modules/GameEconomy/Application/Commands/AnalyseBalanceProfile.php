<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\BalanceAnalysis;
use Modules\GameEconomy\Domain\Events\BalanceAnalysed;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\ValueObjects\BalanceSummary;
use Modules\GameEconomy\Infrastructure\Calculations\BalanceAnalyser;
use Modules\GameEconomy\Infrastructure\Calculations\BalanceCalculator;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\BalanceProfileRepository;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * Read a configuration and report what is in it.
 *
 * A command rather than a query, which looks wrong at first and is deliberate:
 * this dispatches an event. Nothing is written — section 31 is absolute, and
 * there is not a line here that changes a value — but the *act of looking* is a
 * fact worth publishing, because whether a team analyses before every playtest
 * or only after something goes wrong is a question about their process that
 * cannot be reconstructed from the data afterwards.
 *
 * The findings themselves are not persisted — section 28. An analysis is a
 * reading of the configuration as it stands, and storing one would immediately
 * create a second question the module would have to keep answering: is this
 * still true?
 *
 * Anything that only wants the numbers, without announcing that it looked,
 * should call `GetBalanceAnalysis` instead.
 */
final class AnalyseBalanceProfile
{
    public function __construct(
        private readonly BalanceAnalyser $analyser,
        private readonly BalanceCalculator $calculator,
        private readonly EconomyRepository $economy,
        private readonly BalanceProfileRepository $profiles,
    ) {}

    public function handle(BalanceProfile $profile, bool $announce = true): BalanceAnalysis
    {
        $resources = $this->economy->resourcesOf($profile);
        $flows = $this->economy->flowsOf($profile);
        $actions = $this->economy->actionsWithEconomicsOf($profile);
        $variables = $this->economy->variablesOf($profile);

        $warnings = $this->analyser->analyse($profile);

        $errors = count(array_filter($warnings, fn ($warning): bool => $warning->isError()));

        $summary = new BalanceSummary(
            resources: $resources->count(),
            flows: $flows->count(),
            actions: $actions->count(),
            costs: $actions->sum(fn ($action): int => $action->costs->count()),
            rewards: $actions->sum(fn ($action): int => $action->rewards->count()),
            effects: $actions->sum(fn ($action): int => $action->effects->count()),
            variables: $variables->count(),
            scenarios: $this->economy->scenariosOf($profile)->count(),
            assumptions: $this->profiles->assumptionsOf($profile)->count(),
            observations: $this->profiles->observationsOf($profile)->count(),
            warnings: count($warnings) - $errors,
            errors: $errors,
        );

        if ($announce) {
            event(new BalanceAnalysed(
                profileId: $profile->getKey(),
                warnings: $summary->warnings,
                errors: $summary->errors,
            ));
        }

        return new BalanceAnalysis(
            profile: $profile,
            resources: $resources,
            flows: $flows,
            actions: $actions,
            variables: $variables,
            netFlows: $this->calculator->computeNetFlows($resources, $flows, $actions),
            profitability: $this->calculator->profitability($profile),
            conversions: $this->calculator->conversions($profile),
            warnings: $warnings,
            summary: $summary,
        );
    }
}
