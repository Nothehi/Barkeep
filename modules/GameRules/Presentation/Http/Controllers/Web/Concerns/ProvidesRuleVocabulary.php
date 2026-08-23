<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web\Concerns;

use BackedEnum;
use Modules\GameRules\Domain\Enums\ConditionOperator;
use Modules\GameRules\Domain\Enums\ConditionType;
use Modules\GameRules\Domain\Enums\Contracts\Described;
use Modules\GameRules\Domain\Enums\Contracts\Labelled;
use Modules\GameRules\Domain\Enums\EffectType;
use Modules\GameRules\Domain\Enums\GamePhaseType;
use Modules\GameRules\Domain\Enums\LogicOperator;
use Modules\GameRules\Domain\Enums\MechanicCategory;
use Modules\GameRules\Domain\Enums\ReferenceType;
use Modules\GameRules\Domain\Enums\RequirementType;
use Modules\GameRules\Domain\Enums\RuleActionType;
use Modules\GameRules\Domain\Enums\RuleSetStatus;
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\Enums\RuleType;
use Modules\GameRules\Domain\Enums\TriggerType;

/**
 * The vocabulary the rules screens choose from, worded by the server.
 *
 * Every picker in this module renders what it is given rather than keeping its
 * own copy of the list. That is what stops a rule type renamed in the domain from
 * still reading the old way in the interface, and it is why these arrays are built
 * from the enums rather than restated in TypeScript.
 *
 * The extra fields travel with the options that need them — `expects_value` and
 * `symbol` on an operator, `expects_value` and `moves_play` on an effect type,
 * `is_entry` and `is_terminal` on a phase type, `is_directed` on a reference type
 * — so a form can decide whether to show a field without holding a second copy of
 * which cases imply what. The condition builder is the one that would suffer most
 * otherwise: it has to know not to draw a value box beside "is true", and that
 * fact belongs to `ConditionOperator`.
 */
trait ProvidesRuleVocabulary
{
    /**
     * The full vocabulary, for the dashboard and the builder.
     *
     * @return array<string, mixed>
     */
    protected function ruleVocabulary(): array
    {
        return [
            'rule_set_statuses' => $this->simpleOptions(RuleSetStatus::cases()),
            'rule_statuses' => $this->describedOptions(RuleStatus::cases()),
            'rule_types' => $this->describedOptions(RuleType::cases()),
            'mechanic_categories' => $this->describedOptions(MechanicCategory::cases()),
            'phase_types' => array_map(
                fn (GamePhaseType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'description' => $type->description(),
                    'is_entry' => $type->isEntry(),
                    'is_terminal' => $type->isTerminal(),
                ],
                GamePhaseType::cases(),
            ),
            'action_types' => $this->describedOptions(RuleActionType::cases()),
            'requirement_types' => array_map(
                fn (RequirementType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'description' => $type->description(),
                    'is_economic' => $type->isEconomic(),
                ],
                RequirementType::cases(),
            ),
            'condition_types' => $this->describedOptions(ConditionType::cases()),
            'operators' => array_map(
                fn (ConditionOperator $operator): array => [
                    'value' => $operator->value,
                    'label' => $operator->label(),
                    'symbol' => $operator->symbol(),
                    'expects_value' => $operator->expectsValue(),
                    'expects_list' => $operator->expectsList(),
                    'expects_number' => $operator->expectsNumber(),
                ],
                ConditionOperator::cases(),
            ),
            'logic_operators' => $this->describedOptions(LogicOperator::cases()),
            'effect_types' => array_map(
                fn (EffectType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'description' => $type->description(),
                    'expects_value' => $type->expectsValue(),
                    'is_economic' => $type->isEconomic(),
                    'moves_play' => $type->movesPlay(),
                ],
                EffectType::cases(),
            ),
            'trigger_types' => $this->describedOptions(TriggerType::cases()),
            'reference_types' => array_map(
                fn (ReferenceType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'description' => $type->description(),
                    'is_directed' => $type->isDirected(),
                ],
                ReferenceType::cases(),
            ),
        ];
    }

    /**
     * Options that carry a label and nothing else.
     *
     * The parameter is an intersection because the two halves come from different
     * places: `Labelled` says the case knows how it reads, and `BackedEnum` says
     * it has a value to send. Neither on its own would be enough to build an
     * option out of.
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
