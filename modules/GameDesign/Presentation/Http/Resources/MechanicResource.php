<?php

namespace Modules\GameDesign\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\GameDesign\Infrastructure\Authorization\MechanicPermissions;
use Modules\Identity\Domain\Models\User;

/**
 * The representation of one term in the design vocabulary.
 *
 * The category travels as a value and a label, matching how every other enum in
 * this module reaches the client: the label is the server's, so a picker that
 * groups by category cannot end up with its own translation of the heading.
 *
 * `is_available` is sent rather than left to be inferred from the status,
 * because "may a game claim this?" is the only question a picker asks and
 * deriving it client-side would be the client reimplementing the rule.
 *
 * The name and the definition go through `__()`. The vocabulary is the
 * platform's rather than a studio's, and its whole purpose is that two games
 * working the same way say so with the same word — so the canonical term is
 * stored in English and stays the stable identity behind the slug, while each
 * reader is shown it in their own language. A curator's own new term has no
 * catalogue entry and passes straight through, which is exactly what `__()`
 * does with a phrase it does not know.
 *
 * No usage count. How many games claim a term is a genuinely interesting figure
 * and counting it here would make the vocabulary read every studio's data on
 * every request — the mechanic knows nothing about who claimed it, and that is
 * what keeps this list cheap enough to send with every game settings screen.
 *
 * @mixin Mechanic
 */
class MechanicResource extends JsonResource
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
            'name' => __($this->name),
            'slug' => $this->slug,
            'description' => $this->description === null ? null : __($this->description),
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'category_position' => $this->category->position(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_available' => $this->allowsAdoption(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'permissions' => $this->permissionsFor($request),
        ];
    }

    /**
     * Resolve what the caller may do with this term.
     *
     * @return array<string, bool>
     */
    private function permissionsFor(Request $request): array
    {
        $user = $request->user();

        return $user instanceof User
            ? app(MechanicPermissions::class)->for($user, $this->resource)
            : MechanicPermissions::none();
    }
}
