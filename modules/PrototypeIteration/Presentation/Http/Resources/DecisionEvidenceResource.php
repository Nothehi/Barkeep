<?php

namespace Modules\PrototypeIteration\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\PrototypeIteration\Domain\ValueObjects\CitedEvidence;

/**
 * The representation of one citation, resolved.
 *
 * Wraps the resolved value object rather than the stored row, which is the whole point: what a
 * reader needs is "Observation: players spent less time waiting", not "cites 9f0c…". The
 * excerpt was read live from the context that owns it at the moment of the request, so a
 * correction to an observation shows up in every decision that cited it.
 *
 * `playtest_id` is what makes section 45's requirement work — clicking a piece of evidence
 * navigates to the context that owns it. It is the playtest rather than the observation because
 * the playtest is the addressable thing in Playtesting, and because this module has no business
 * publishing routes for somebody else's records.
 *
 * `is_resolved` false is a first-class state rather than an error. The reference is deliberately
 * loose — no foreign key, so that this module holds no copy of the evidence — and the commonest
 * reason a citation fails to resolve is not deletion but permission: a reader who can see the
 * iteration and not the playtest. The interface renders it as a citation it cannot show, which
 * is honest, where a dropped row would read as "nothing supported this".
 *
 * @mixin CitedEvidence
 */
class DecisionEvidenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->typeLabel,
            'reference_id' => $this->referenceId,

            /*
             * The reason it was cited, written by the studio. Never a copy of the cited words.
             */
            'description' => $this->description,

            /*
             * The cited words themselves, read live from the owning context.
             */
            'excerpt' => $this->excerpt,
            'attribution' => $this->attribution,

            'playtest_id' => $this->playtestId,
            'is_resolved' => $this->isResolved,
            'is_linkable' => $this->isLinkable(),
        ];
    }
}
