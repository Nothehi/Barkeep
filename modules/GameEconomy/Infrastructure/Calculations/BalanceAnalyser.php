<?php

namespace Modules\GameEconomy\Infrastructure\Calculations;

use Modules\GameEconomy\Domain\Enums\BalanceEntityType;
use Modules\GameEconomy\Domain\Enums\BalanceWarningCode;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\ValueObjects\BalanceWarning;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * Everything the analysis knows how to notice.
 *
 * Deterministic, in the sense section 27 of the brief means: the same
 * configuration produces the same list every time, from rules a designer can
 * learn once and then trust. There is no scoring, no heuristic and no threshold
 * anybody has to tune — which is the difference between a check somebody acts on
 * and a check somebody turns off.
 *
 * It reports and never writes. Not a single one of these findings changes a
 * value, and that is section 31: a half-built economy is full of them, and a
 * tool that refused to save one would be a tool nobody could start with. The
 * designer reads the list and decides.
 *
 * Each check is its own method, named for what it looks for, so that adding one
 * is adding a method and a case to {@see BalanceWarningCode} rather than editing
 * a long conditional. The severity comes from the code rather than from here, so
 * "an uncapped resource is a warning, not an error" has exactly one definition.
 */
final class BalanceAnalyser
{
    public function __construct(
        private readonly EconomyRepository $economy,
        private readonly BalanceCalculator $calculator,
    ) {}

    /**
     * Every finding in a configuration, worst first.
     *
     * The ordering is by severity and then by the order the checks run, which
     * puts the errors at the top of the screen where somebody triaging will read
     * them — and keeps the rest in a stable order so a list does not reshuffle
     * between two identical analyses.
     *
     * @return list<BalanceWarning>
     */
    public function analyse(BalanceProfile $profile): array
    {
        $resources = $this->economy->resourcesOf($profile);
        $flows = $this->economy->flowsOf($profile);
        $actions = $this->economy->actionsWithEconomicsOf($profile);
        $variables = $this->economy->variablesOf($profile);

        $netFlows = $this->calculator->computeNetFlows($resources, $flows, $actions);

        $findings = [];

        if ($resources->isEmpty()) {
            $findings[] = BalanceWarning::about(
                BalanceWarningCode::ProfileHasNoResources,
                BalanceEntityType::Profile,
                $profile->getKey(),
                $profile->name,
                __('This balance profile has no resources yet.'),
            );
        }

        if ($actions->isEmpty() && $resources->isNotEmpty()) {
            $findings[] = BalanceWarning::about(
                BalanceWarningCode::ProfileHasNoActions,
                BalanceEntityType::Profile,
                $profile->getKey(),
                $profile->name,
                __('This balance profile has resources but no actions that move them.'),
            );
        }

        foreach ($resources as $resource) {
            $flow = $netFlows[$resource->getKey()] ?? null;

            if ($flow === null) {
                continue;
            }

            if (! $flow->hasGeneration()) {
                $findings[] = BalanceWarning::about(
                    BalanceWarningCode::ResourceHasNoGeneration,
                    BalanceEntityType::Resource,
                    $resource->getKey(),
                    $resource->name,
                    __('Nothing generates :resource, so nobody can ever spend it.', ['resource' => $resource->name]),
                );
            }

            if ($resource->is_spendable && ! $flow->hasConsumption()) {
                $findings[] = BalanceWarning::about(
                    BalanceWarningCode::ResourceHasNoConsumption,
                    BalanceEntityType::Resource,
                    $resource->getKey(),
                    $resource->name,
                    __('Nothing spends :resource, so holding it costs a player nothing.', ['resource' => $resource->name]),
                );
            }

            /*
             * The runaway shape, and the reason it is checked separately from
             * "no consumption" above. A resource that arrives, accumulates and
             * never leaves does not merely sit still — it grows without bound as
             * the game goes on, which is the commonest way a long game stops
             * having decisions in it.
             */
            if ($resource->is_accumulative && $flow->hasGeneration() && ! $flow->hasConsumption()) {
                $findings[] = BalanceWarning::about(
                    BalanceWarningCode::ResourceAccumulatesWithoutSink,
                    BalanceEntityType::Resource,
                    $resource->getKey(),
                    $resource->name,
                    __(':resource is generated and never spent, so it grows without limit.', ['resource' => $resource->name]),
                );
            }

            if ($resource->is_accumulative && $flow->hasGeneration() && ! $resource->hasMaximum()) {
                $findings[] = BalanceWarning::about(
                    BalanceWarningCode::ResourceGenerationIsUncapped,
                    BalanceEntityType::Resource,
                    $resource->getKey(),
                    $resource->name,
                    __(':resource is produced with no maximum, so nothing bounds how much a player can hold.', ['resource' => $resource->name]),
                );
            }

            if ($resource->starting_value !== null && ! $resource->allows($resource->starting_value)) {
                $findings[] = BalanceWarning::about(
                    BalanceWarningCode::ResourceStartsOutsideItsRange,
                    BalanceEntityType::Resource,
                    $resource->getKey(),
                    $resource->name,
                    __('Players start with :amount :resource, which is outside the range set for it.', [
                        'amount' => $resource->starting_value->label(),
                        'resource' => $resource->name,
                    ]),
                );
            }
        }

        foreach ($flows as $flow) {
            if ($flow->amount->isZero()) {
                $findings[] = BalanceWarning::about(
                    BalanceWarningCode::FlowHasNoAmount,
                    BalanceEntityType::Flow,
                    $flow->getKey(),
                    $flow->name,
                    __('":flow" moves nothing, so it has no effect on the economy.', ['flow' => $flow->name]),
                );
            }
        }

        foreach ($actions as $action) {
            $profitability = $this->calculator->profitabilityOf($action);

            if (! $profitability->hasCost()) {
                $findings[] = BalanceWarning::about(
                    BalanceWarningCode::ActionHasNoCost,
                    BalanceEntityType::Action,
                    $action->getKey(),
                    $action->name,
                    __('":action" costs nothing, so there is no reason not to take it every turn.', ['action' => $action->name]),
                );
            }

            if (! $profitability->hasOutcome()) {
                $findings[] = BalanceWarning::about(
                    BalanceWarningCode::ActionHasNoOutcome,
                    BalanceEntityType::Action,
                    $action->getKey(),
                    $action->name,
                    __('":action" pays nothing and changes nothing, so nobody would take it.', ['action' => $action->name]),
                );
            }

            foreach ($profitability->multipliedResources() as $delta) {
                $findings[] = BalanceWarning::about(
                    BalanceWarningCode::ActionMultipliesAResource,
                    BalanceEntityType::Action,
                    $action->getKey(),
                    $action->name,
                    __('":action" returns more :resource than it takes, so repeating it produces value from nothing.', [
                        'action' => $action->name,
                        'resource' => $delta->resourceName,
                    ]),
                );
            }

            foreach ($action->rewards as $reward) {
                $resource = $reward->resource;

                if ($resource === null || ! $resource->hasMaximum()) {
                    continue;
                }

                if ($reward->highestAmount()->isGreaterThan($resource->max_value)) {
                    $findings[] = BalanceWarning::about(
                        BalanceWarningCode::ActionRewardExceedsMaximum,
                        BalanceEntityType::Reward,
                        $reward->getKey(),
                        $action->name,
                        __('":action" pays out up to :amount :resource, above the maximum of :maximum.', [
                            'action' => $action->name,
                            'amount' => $reward->highestAmount()->label(),
                            'resource' => $resource->name,
                            'maximum' => $resource->max_value->label(),
                        ]),
                    );
                }

                if ($reward->isUnboundedVariable()) {
                    $findings[] = BalanceWarning::about(
                        BalanceWarningCode::VariableAmountHasNoRange,
                        BalanceEntityType::Reward,
                        $reward->getKey(),
                        $action->name,
                        __('":action" pays a variable amount of :resource with no range given.', [
                            'action' => $action->name,
                            'resource' => $resource->name,
                        ]),
                    );
                }
            }

            foreach ($action->costs as $cost) {
                if (! $cost->isUnboundedVariable()) {
                    continue;
                }

                $findings[] = BalanceWarning::about(
                    BalanceWarningCode::VariableAmountHasNoRange,
                    BalanceEntityType::Cost,
                    $cost->getKey(),
                    $action->name,
                    __('":action" costs a variable amount of :resource with no range given.', [
                        'action' => $action->name,
                        'resource' => $cost->resource->name,
                    ]),
                );
            }
        }

        foreach ($variables as $variable) {
            if (! $variable->isWithinItsRange()) {
                $findings[] = BalanceWarning::about(
                    BalanceWarningCode::VariableIsOutsideItsRange,
                    BalanceEntityType::Variable,
                    $variable->getKey(),
                    $variable->name,
                    __(':variable is :value, outside the range :minimum to :maximum.', [
                        'variable' => $variable->name,
                        'value' => $variable->value->label(),
                        'minimum' => $variable->min_value?->label() ?? '−∞',
                        'maximum' => $variable->max_value?->label() ?? '∞',
                    ]),
                );
            }

            if (! $variable->isWellFormedProbability()) {
                $findings[] = BalanceWarning::about(
                    BalanceWarningCode::ProbabilityIsOutsideZeroToOne,
                    BalanceEntityType::Variable,
                    $variable->getKey(),
                    $variable->name,
                    __(':variable is :value. Probabilities are written between 0 and 1.', [
                        'variable' => $variable->name,
                        'value' => $variable->value->label(),
                    ]),
                );
            }
        }

        return $this->worstFirst($findings);
    }

    /**
     * Sort findings by severity without disturbing the order within each level.
     *
     * A stable sort matters here: two analyses of an unchanged configuration
     * must produce the same list in the same order, or the interface will appear
     * to shuffle its own findings when nothing has happened.
     *
     * @param  list<BalanceWarning>  $findings
     * @return list<BalanceWarning>
     */
    private function worstFirst(array $findings): array
    {
        $indexed = [];

        foreach ($findings as $index => $finding) {
            $indexed[] = [$finding->severity()->weight(), $index, $finding];
        }

        usort($indexed, fn (array $a, array $b): int => [$b[0], $a[1]] <=> [$a[0], $b[1]]);

        return array_map(fn (array $entry): BalanceWarning => $entry[2], $indexed);
    }
}
