<?php

namespace Modules\Playtesting\Application\DTOs;

use Carbon\CarbonImmutable;
use Modules\Playtesting\Domain\Enums\PlaytestParticipantRole;

/**
 * The validated input required to seat somebody at a session.
 *
 * A name and, optionally, an account. That order is the right way round: the
 * common participant is a friend with no Barkeep login, so the name is the
 * required field and the account is the extra.
 *
 * The name is kept even when an account is given. A session should read back
 * the way the room worked — somebody who introduced themselves as "Sam" stays
 * Sam here whatever their profile says a year later.
 */
final readonly class AddParticipantData
{
    public function __construct(
        public string $displayName,
        public PlaytestParticipantRole $role,
        public ?string $userId = null,
        public ?CarbonImmutable $joinedAt = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $role = isset($input['role'])
            ? PlaytestParticipantRole::from((string) $input['role'])
            : PlaytestParticipantRole::default();

        return new self(
            displayName: PlaytestInput::requiredText($input, 'display_name'),
            role: $role,
            userId: PlaytestInput::identifier($input, 'user_id'),
            joinedAt: PlaytestInput::timestamp($input, 'joined_at'),
        );
    }
}
