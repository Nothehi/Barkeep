<?php

namespace Modules\PrototypeIteration\Application\DTOs;

use Modules\PrototypeIteration\Domain\Enums\PrototypeType;

/**
 * The validated input required to start a prototype.
 *
 * The game is absent, and that absence is a rule: it comes from the resolved
 * route binding rather than from the request body, so a caller cannot start a
 * prototype in a game they merely named. The game version id *is* here — there
 * is no route segment for it — which is why it is proved to belong to that game
 * before anything is written.
 *
 * There is no status either. Every prototype starts as a draft.
 */
final readonly class CreatePrototypeData
{
    public function __construct(
        public string $gameVersionId,
        public string $name,
        public ?string $description = null,
        public PrototypeType $type = PrototypeType::Paper,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $type = IterationInput::identifier($input, 'type');

        return new self(
            gameVersionId: (string) $input['game_version_id'],
            name: IterationInput::requiredText($input, 'name'),
            description: IterationInput::text($input, 'description'),
            type: ($type === null ? null : PrototypeType::tryFrom($type)) ?? PrototypeType::default(),
        );
    }
}
