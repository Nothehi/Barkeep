<?php

namespace Modules\GameDesign\Application\DTOs;

use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\ValueObjects\GameSlug;

/**
 * The validated input required to start a new game.
 *
 * The address is optional because leaving it out means something different
 * from supplying one: an absent address is derived from the name and may be
 * adjusted around a collision, whereas a supplied one is taken literally and
 * a collision is reported back.
 *
 * Neither the workspace nor the creator appears here. Both are resolved from
 * authenticated state and the route, never from a request body, so there is
 * no field for a caller to put them in.
 */
final readonly class CreateGameData
{
    public function __construct(
        public string $name,
        public ?GameSlug $slug = null,
        public ?string $description = null,
        public ?DesignPhase $designPhase = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $slug = isset($input['slug']) && $input['slug'] !== '' ? (string) $input['slug'] : null;
        $description = isset($input['description']) && $input['description'] !== '' ? (string) $input['description'] : null;
        $phase = isset($input['design_phase']) && $input['design_phase'] !== '' ? (string) $input['design_phase'] : null;

        return new self(
            name: trim((string) $input['name']),
            slug: $slug === null ? null : GameSlug::fromString($slug),
            description: $description,
            designPhase: $phase === null ? null : DesignPhase::from($phase),
        );
    }

    /**
     * The phase the game should start in.
     *
     * A game whose creator did not say begins as an idea, which is where
     * every game genuinely starts.
     */
    public function designPhaseOrDefault(): DesignPhase
    {
        return $this->designPhase ?? DesignPhase::default();
    }
}
