<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Web\Concerns;

use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;
use Modules\PrototypeIteration\Domain\Enums\DesignChangeCategory;
use Modules\PrototypeIteration\Domain\Enums\EvidenceType;
use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;
use Modules\PrototypeIteration\Domain\Enums\IterationOutcome;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;
use Modules\PrototypeIteration\Domain\Enums\PrototypeArtifactType;
use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;
use Modules\PrototypeIteration\Domain\Enums\PrototypeType;

/**
 * The values this module's screens let somebody choose between.
 *
 * Sent from the server so that the labels, the descriptions, the ordering and the sets themselves
 * have one definition. A client that hard-coded them would be a second opinion waiting to go
 * stale — and two of these lists are the ones most likely to change: the change categories, which
 * the framework system will eventually influence, and the artifact kinds, which will grow as
 * studios file things nobody anticipated.
 *
 * The descriptions travel alongside the labels because several of these choices are genuinely
 * confusable at the moment somebody is making them. The difference between a failed iteration and
 * an inconclusive one is the one people get wrong, and a one-line explanation next to each option
 * is worth more than any amount of documentation nobody opens.
 *
 * A trait rather than a shared base controller, because the four screens that need this have
 * nothing else in common and a base class would be inheritance for the sake of one method.
 */
trait ProvidesDesignVocabulary
{
    /**
     * The vocabulary the prototype screens need.
     *
     * @return array{types: list<array{value: string, label: string, description: string}>, statuses: list<array{value: string, label: string}>, artifact_types: list<array{value: string, label: string, description: string}>}
     */
    protected function prototypeVocabulary(): array
    {
        return [
            'types' => array_map(
                fn (PrototypeType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'description' => $type->description(),
                ],
                PrototypeType::cases(),
            ),
            'statuses' => array_map(
                fn (PrototypeStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                PrototypeStatus::cases(),
            ),
            'artifact_types' => array_map(
                fn (PrototypeArtifactType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'description' => $type->description(),
                ],
                PrototypeArtifactType::cases(),
            ),
        ];
    }

    /**
     * The vocabulary the iteration screens need.
     *
     * @return array{statuses: list<array{value: string, label: string}>, outcomes: list<array{value: string, label: string, description: string}>, change_categories: list<array{value: string, label: string, description: string}>, experiment_statuses: list<array{value: string, label: string}>, decision_statuses: list<array{value: string, label: string}>, evidence_types: list<array{value: string, label: string, requires_reference: bool}>}
     */
    protected function iterationVocabulary(): array
    {
        return [
            'statuses' => array_map(
                fn (IterationStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                IterationStatus::cases(),
            ),
            'outcomes' => array_map(
                fn (IterationOutcome $outcome): array => [
                    'value' => $outcome->value,
                    'label' => $outcome->label(),
                    'description' => $outcome->description(),
                ],
                IterationOutcome::cases(),
            ),
            'change_categories' => array_map(
                fn (DesignChangeCategory $category): array => [
                    'value' => $category->value,
                    'label' => $category->label(),
                    'description' => $category->description(),
                ],
                DesignChangeCategory::cases(),
            ),
            'experiment_statuses' => array_map(
                fn (ExperimentStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                ExperimentStatus::cases(),
            ),
            'decision_statuses' => array_map(
                fn (DecisionStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                DecisionStatus::cases(),
            ),

            /*
             * `requires_reference` travels with each evidence type so the citation form knows
             * whether to show a picker without keeping its own copy of which types point at
             * something. A note is the only one that does not, and it is also the only one whose
             * description field carries the evidence itself — a distinction the client would
             * otherwise have to hard-code.
             */
            'evidence_types' => array_map(
                fn (EvidenceType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'requires_reference' => $type->requiresReference(),
                ],
                EvidenceType::cases(),
            ),
        ];
    }
}
