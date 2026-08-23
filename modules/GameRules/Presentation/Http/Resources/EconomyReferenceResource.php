<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\ValueObjects\EconomyReference;

/**
 * What the game's economy says about something a rule points at.
 *
 * Read live at render time rather than stored, which is the whole of section 34
 * of the brief: the costs on the rules screen always agree with the balance
 * screen because there is only one set of them.
 *
 * `is_resolved` being false is ordinary rather than an error — most rule sets are
 * written before an economy is modelled, and many studios never model one. The
 * interface shows the handle and moves on.
 *
 * The summary is already-worded text produced by the economy adapter. This module
 * does not format amounts, because it does not know that amounts are exact
 * decimals and must not learn.
 *
 * @mixin EconomyReference
 */
class EconomyReferenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'handle' => $this->handle,
            'is_resolved' => $this->isResolved,
            'label' => $this->label,
            'summary' => $this->summary,
        ];
    }
}
