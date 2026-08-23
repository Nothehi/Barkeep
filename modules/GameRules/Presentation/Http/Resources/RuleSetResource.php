<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameDesign\Presentation\Http\Resources\GameVersionResource;
use Modules\GameRules\Domain\Enums\RuleSetStatus;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Authorization\RuleSetPermissions;
use Modules\Identity\Domain\Models\User;
use Modules\Identity\Presentation\Http\Resources\UserResource;

/**
 * The representation of one rule system.
 *
 * Three things travel with it that the columns do not hold, and each is here so
 * the client does not have to work it out:
 *
 * - `status_label` and `available_transitions`, already worded by the enum, so a
 *   status renamed in the domain reads the new way in the interface without
 *   anything in TypeScript changing;
 * - `permissions`, the policy's own answer, so an "Add rule" button is drawn
 *   because the server said so rather than because the client re-derived the
 *   rules from a status;
 * - the counts, so a list can say "24 rules, 7 phases" without loading either.
 *
 * `permissions.canEdit` is the one nearly every control reads, and `canClone` is
 * the one that matters on an active set where everything else is refused —
 * without it an interface would show a read-only screen with no way forward.
 *
 * @mixin RuleSet
 */
class RuleSetResource extends JsonResource
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
            'game_version_id' => $this->game_version_id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_editable' => $this->isModifiable(),
            'cloned_from_rule_set_id' => $this->cloned_from_rule_set_id,
            'available_transitions' => $this->transitions(),
            'version' => GameVersionResource::make($this->whenLoaded('version')),
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'rules_count' => $this->whenCounted('rules'),
            'mechanics_count' => $this->whenCounted('mechanics'),
            'phases_count' => $this->whenCounted('phases'),
            'actions_count' => $this->whenCounted('actions'),
            'conditions_count' => $this->whenCounted('conditions'),
            'permissions' => $this->permissions($request),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * The moves this rule set may make, already worded.
     *
     * The interface renders these as buttons rather than deciding which moves are
     * legal — the lifecycle matrix lives on the enum and nowhere else.
     *
     * @return list<array{status: string, label: string}>
     */
    private function transitions(): array
    {
        return array_map(
            fn (RuleSetStatus $target): array => [
                'status' => $target->value,
                'label' => $this->status->transitionLabelTo($target),
            ],
            $this->status->transitions(),
        );
    }

    /**
     * What the signed in account may do with this rule set.
     *
     * @return array<string, bool>
     */
    private function permissions(Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return RuleSetPermissions::none();
        }

        /** @var RuleSet $ruleSet */
        $ruleSet = $this->resource;

        return app(RuleSetPermissions::class)->for($user, $ruleSet);
    }
}
