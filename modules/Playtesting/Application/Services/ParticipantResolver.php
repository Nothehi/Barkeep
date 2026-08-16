<?php

namespace Modules\Playtesting\Application\Services;

use Modules\Playtesting\Domain\Exceptions\ParticipantDoesNotBelongToSession;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;

/**
 * The one place a participant id from a request body is turned into a person.
 *
 * Every other identifier in the module arrives through a route binding and is
 * therefore already scoped by the chain that resolved it — workspace, game,
 * playtest, session. A participant id is the exception: observations and
 * feedback name one in their body, so it is the only id that could belong to
 * somewhere else entirely.
 *
 * Resolving it *through* the session closes that. A participant from another
 * session is not compared against this one and rejected; the lookup simply
 * does not find them.
 *
 * Getting this wrong would be worse than a leak. Attaching one session's
 * feedback to another session's participant produces a record that reads
 * perfectly and is false — and nobody would ever have reason to check it.
 */
final class ParticipantResolver
{
    public function __construct(private readonly PlaytestRepository $playtests) {}

    /**
     * Resolve an optional participant within a session.
     *
     * A null id is a complete answer rather than a missing one. Plenty of
     * observations are about the table rather than about a person, and plenty
     * of feedback is given anonymously.
     *
     * @throws ParticipantDoesNotBelongToSession when the id names somebody else's participant
     */
    public function resolve(PlaytestSession $session, ?string $participantId): ?PlaytestParticipant
    {
        if ($participantId === null) {
            return null;
        }

        return $this->playtests->findParticipantInSession($session, $participantId)
            ?? throw ParticipantDoesNotBelongToSession::forPair($participantId, $session->getKey());
    }
}
