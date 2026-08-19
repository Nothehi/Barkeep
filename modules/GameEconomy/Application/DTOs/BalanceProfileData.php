<?php

namespace Modules\GameEconomy\Application\DTOs;

/**
 * The validated input required to create or rename a balance configuration.
 *
 * The design version is absent, and that absence is a rule: it comes from the
 * resolved route binding rather than from the request body, so a caller cannot
 * configure the economy of a version they merely named.
 *
 * There is no status either. Every profile starts as a draft, and moving it on
 * is an action with its own endpoint — which is what keeps an irreversible move
 * from being one field value away from a reversible one.
 */
final readonly class BalanceProfileData
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public bool $descriptionWasSent = false,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * `descriptionWasSent` is carried separately so that an update can tell "do
     * not touch the description" from "clear the description" — a partial update
     * that could not would erase every field it did not mention.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            name: EconomyInput::text($input, 'name'),
            description: EconomyInput::text($input, 'description'),
            descriptionWasSent: EconomyInput::has($input, 'description'),
        );
    }
}
