<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\ValueObjects\FieldChange;
use Modules\GameEconomy\Domain\ValueObjects\SnapshotChange;
use Modules\GameEconomy\Domain\ValueObjects\SnapshotComparison;

/**
 * The representation of the difference between two frozen configurations.
 *
 * Grouped by what kind of thing changed rather than flattened, because that is
 * how the question gets asked: "what happened to the variables?" is a different
 * question from "what resources did we add?", and one list would make both a
 * filtering exercise.
 *
 * Direction is fixed and stated. `from` is the earlier snapshot, so a reader can
 * take "10 → 12" at face value without checking which way round the request was.
 *
 * @mixin SnapshotComparison
 */
class BalanceComparisonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'from' => [
                'id' => $this->fromSnapshotId,
                'name' => $this->fromSnapshotName,
            ],
            'to' => [
                'id' => $this->toSnapshotId,
                'name' => $this->toSnapshotName,
            ],
            'resources' => array_map($this->renderChange(...), $this->resources),
            'flows' => array_map($this->renderChange(...), $this->flows),
            'actions' => array_map($this->renderChange(...), $this->actions),
            'costs' => array_map($this->renderChange(...), $this->costs),
            'rewards' => array_map($this->renderChange(...), $this->rewards),
            'effects' => array_map($this->renderChange(...), $this->effects),
            'variables' => array_map($this->renderChange(...), $this->variables),
            'count' => $this->count(),
            'is_identical' => $this->isIdentical(),
        ];
    }

    /**
     * Render one changed record.
     *
     * @return array<string, mixed>
     */
    private function renderChange(SnapshotChange $change): array
    {
        return [
            'type' => $change->type->value,
            'type_label' => $change->type->label(),
            'entity_type' => $change->entityType->value,
            'entity_type_label' => $change->entityType->label(),
            'key' => $change->key,
            'label' => $change->label,
            'fields' => array_map(
                fn (FieldChange $field): array => [
                    'field' => $field->field,
                    'label' => $field->label,
                    'before' => $field->before,
                    'after' => $field->after,
                ],
                $change->fields,
            ),
        ];
    }
}
