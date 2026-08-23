<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\ValueObjects\RuleGraph;
use Modules\GameRules\Domain\ValueObjects\RuleGraphEdge;
use Modules\GameRules\Domain\ValueObjects\RuleGraphNode;

/**
 * The flow of a game, ready to draw.
 *
 * Nodes and edges with labels already worded, because the diagram is read-only:
 * there is nothing here for a client to edit, so there is nothing it needs the
 * underlying records for. Editing happens in the phase designer.
 *
 * `is_implicit` marks the arrows the rule set did not state — mainly the one into
 * the first phase — so the interface can draw them faintly. Somebody should be
 * able to tell what they wrote from what was inferred on their behalf.
 *
 * @mixin RuleGraph
 */
class RuleGraphResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'nodes' => array_map(
                fn (RuleGraphNode $node): array => [
                    'key' => $node->key,
                    'entity_type' => $node->entityType->value,
                    'entity_id' => $node->entityId,
                    'label' => $node->label,
                    'detail' => $node->detail,
                    'is_entry' => $node->isEntry,
                    'is_terminal' => $node->isTerminal,
                    'is_reachable' => $this->reaches($node->key),
                    'actions' => $node->actions,
                ],
                $this->nodes,
            ),
            'edges' => array_map(
                fn (RuleGraphEdge $edge): array => [
                    'from' => $edge->from,
                    'to' => $edge->to,
                    'label' => $edge->label,
                    'entity_id' => $edge->entityId,
                    'is_implicit' => $edge->isImplicit,
                ],
                $this->edges,
            ),
            'unreachable' => $this->unreachable,
            'is_empty' => $this->isEmpty(),
        ];
    }
}
