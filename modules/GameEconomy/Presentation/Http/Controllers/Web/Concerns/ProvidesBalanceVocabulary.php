<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Web\Concerns;

use BackedEnum;
use Modules\GameEconomy\Domain\Enums\ActionEffectType;
use Modules\GameEconomy\Domain\Enums\AssumptionCategory;
use Modules\GameEconomy\Domain\Enums\AssumptionConfidence;
use Modules\GameEconomy\Domain\Enums\BalanceProfileStatus;
use Modules\GameEconomy\Domain\Enums\BalanceScenarioStatus;
use Modules\GameEconomy\Domain\Enums\BalanceVariableCategory;
use Modules\GameEconomy\Domain\Enums\Contracts\Described;
use Modules\GameEconomy\Domain\Enums\Contracts\Labelled;
use Modules\GameEconomy\Domain\Enums\ObservationSeverity;
use Modules\GameEconomy\Domain\Enums\ObservationSourceType;
use Modules\GameEconomy\Domain\Enums\ResourceCategory;
use Modules\GameEconomy\Domain\Enums\ResourceFlowType;

/**
 * The vocabulary the balance screens choose from, worded by the server.
 *
 * Every picker in this module renders what it is given rather than keeping its
 * own copy of the list. That is what stops a category renamed in the domain from
 * still reading the old way in the interface, and it is why these arrays are
 * built from the enums rather than restated in TypeScript.
 *
 * The extra booleans travel with the options that need them —
 * `expects_value` on an effect type, `expects_reference` on an observation
 * source, `direction` on a flow type — so a form can decide whether to show a
 * field without holding a second copy of which cases imply what.
 */
trait ProvidesBalanceVocabulary
{
    /**
     * The full vocabulary, for the dashboard.
     *
     * @return array<string, mixed>
     */
    protected function balanceVocabulary(): array
    {
        return [
            'profile_statuses' => $this->simpleOptions(BalanceProfileStatus::cases()),
            'resource_categories' => $this->describedOptions(ResourceCategory::cases()),
            'flow_types' => array_map(
                fn (ResourceFlowType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'description' => $type->description(),
                    'direction' => $type->direction(),
                ],
                ResourceFlowType::cases(),
            ),
            'effect_types' => array_map(
                fn (ActionEffectType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'description' => $type->description(),
                    'expects_value' => $type->expectsValue(),
                ],
                ActionEffectType::cases(),
            ),
            'variable_categories' => $this->describedOptions(BalanceVariableCategory::cases()),
            'scenario_statuses' => $this->simpleOptions(BalanceScenarioStatus::cases()),
            'assumption_categories' => $this->describedOptions(AssumptionCategory::cases()),
            'confidences' => $this->describedOptions(AssumptionConfidence::cases()),
            'observation_severities' => $this->describedOptions(ObservationSeverity::cases()),
            'observation_sources' => array_map(
                fn (ObservationSourceType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'description' => $type->description(),
                    'expects_reference' => $type->expectsReference(),
                ],
                ObservationSourceType::cases(),
            ),
        ];
    }

    /**
     * Options that carry a label and nothing else.
     *
     * The parameter is an intersection because the two halves come from
     * different places: `Labelled` says the case knows how it reads, and
     * `BackedEnum` says it has a value to send. Neither on its own would be
     * enough to build an option out of.
     *
     * @param  list<Labelled&BackedEnum>  $cases
     * @return list<array{value: string, label: string}>
     */
    private function simpleOptions(array $cases): array
    {
        return array_map(
            fn (Labelled&BackedEnum $case): array => [
                'value' => (string) $case->value,
                'label' => $case->label(),
            ],
            $cases,
        );
    }

    /**
     * Options that explain what belongs under them.
     *
     * @param  list<Described&BackedEnum>  $cases
     * @return list<array{value: string, label: string, description: string}>
     */
    private function describedOptions(array $cases): array
    {
        return array_map(
            fn (Described&BackedEnum $case): array => [
                'value' => (string) $case->value,
                'label' => $case->label(),
                'description' => $case->description(),
            ],
            $cases,
        );
    }
}
