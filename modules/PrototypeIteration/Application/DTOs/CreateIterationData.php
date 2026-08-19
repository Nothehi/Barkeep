<?php

namespace Modules\PrototypeIteration\Application\DTOs;

/**
 * The validated input required to plan a turn of the design loop.
 *
 * The game is absent because it comes from the resolved route binding rather
 * than from the request body. The two version ids are here because there is no
 * route segment for either, and they are the reason this DTO carries the
 * module's central invariant across the boundary: both are proved to belong to
 * that game — the design state through GameDesign's own relation, the prototype
 * version through the game's own prototypes — before anything is written.
 *
 * There is no status, no outcome and no summary. Every iteration starts planned,
 * with nothing yet to say about how it went.
 */
final readonly class CreateIterationData
{
    public function __construct(
        public string $gameVersionId,
        public string $prototypeVersionId,
        public string $title,
        public string $objective,
        public ?string $hypothesis = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            gameVersionId: (string) $input['game_version_id'],
            prototypeVersionId: (string) $input['prototype_version_id'],
            title: IterationInput::requiredText($input, 'title'),
            objective: IterationInput::requiredText($input, 'objective'),
            hypothesis: IterationInput::text($input, 'hypothesis'),
        );
    }
}
