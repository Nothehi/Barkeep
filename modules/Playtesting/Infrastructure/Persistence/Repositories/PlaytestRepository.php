<?php

namespace Modules\Playtesting\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\DTOs\PlaytestFilters;
use Modules\Playtesting\Application\DTOs\PlaytestSummary;
use Modules\Playtesting\Domain\Enums\PlaytestParticipantRole;
use Modules\Playtesting\Domain\Enums\PlaytestSessionStatus;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Domain\ValueObjects\SessionDuration;

/**
 * Every read the module performs against its own tables.
 *
 * Collecting them here is what makes "a playtest is only ever visible through
 * its game" checkable: there is one method that lists playtests, it takes a
 * game, and no query elsewhere gets the chance to forget the scope. The game
 * itself was resolved through a workspace, so the whole ownership chain —
 * workspace, game, playtest, session — holds by construction rather than by
 * each caller remembering to check it.
 *
 * Nothing here authorizes. Resolving a record and deciding who may see it are
 * separate steps, and every caller runs a policy on the result; merging the
 * two would make it easy to forget the second half.
 */
final class PlaytestRepository
{
    /**
     * The playtests of a game, most recently planned first.
     *
     * The game is a parameter rather than a filter, so there is no way to call
     * this without one. Ordering puts the newest investigation at the top and
     * falls back to creation time for playtests with no planned date, so the
     * order is total rather than leaving undated rows to the database's whim.
     *
     * @return Collection<int, Playtest>
     */
    public function forGame(Game $game, ?PlaytestFilters $filters = null): Collection
    {
        $filters ??= PlaytestFilters::none();

        $playtests = Playtest::query()
            ->where('game_id', $game->getKey())
            ->when(
                $filters->status !== null,
                fn (Builder $query) => $query->where('status', $filters->status),
            )
            ->when(
                $filters->search !== null,
                fn (Builder $query) => $this->applySearch($query, (string) $filters->search),
            )
            ->with('version')
            ->withCount('sessions')
            ->orderByRaw('CASE WHEN planned_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('planned_at')
            ->orderByDesc('created_at')
            ->get();

        return $this->withGame($game, $playtests);
    }

    /**
     * Find one of a game's playtests by id.
     *
     * Scoped to the game for the same reason the list is: it is how the
     * playtest is identified at all on a nested route, and it means a playtest
     * from another game fails to resolve rather than being caught later by a
     * policy.
     */
    public function findForGame(Game $game, string $playtestId): ?Playtest
    {
        $playtest = Playtest::query()
            ->where('game_id', $game->getKey())
            ->whereKey($playtestId)
            ->with(['version', 'creator'])
            ->first();

        return $playtest === null ? null : $playtest->setRelation('game', $game);
    }

    /**
     * Find one of a playtest's sessions by id.
     */
    public function findSessionForPlaytest(Playtest $playtest, string $sessionId): ?PlaytestSession
    {
        $session = $playtest->sessions()
            ->whereKey($sessionId)
            ->with('creator')
            ->first();

        return $session === null ? null : $session->setRelation('playtest', $playtest);
    }

    /**
     * A playtest's sittings, earliest planned first.
     *
     * Ordered forwards rather than backwards, because sessions are read as a
     * sequence — "we tried it with four groups, and by the third they stopped
     * asking about scoring" only makes sense in order.
     *
     * @return Collection<int, PlaytestSession>
     */
    public function sessionsOf(Playtest $playtest): Collection
    {
        return $playtest->sessions()
            ->with('creator')
            ->withCount(['participants', 'observations', 'feedback'])
            ->orderByRaw('CASE WHEN planned_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('planned_at')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * The people at a session, in the order they were added.
     *
     * @return Collection<int, PlaytestParticipant>
     */
    public function participantsOf(PlaytestSession $session): Collection
    {
        return $session->participants()
            ->with('user')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Find one of a session's participants by id.
     *
     * The lookup behind attributing evidence. A participant id arrives in a
     * request body rather than through a route binding, so scoping it to the
     * session here is what stops one session's feedback from being pinned to
     * another session's participant.
     */
    public function findParticipantInSession(PlaytestSession $session, string $participantId): ?PlaytestParticipant
    {
        return $session->participants()->whereKey($participantId)->first();
    }

    /**
     * What was noticed at a session, in the order it was noticed.
     *
     * Sorted by the moment of observation where there is one and by when it
     * was written down otherwise, which is what makes the timeline readable:
     * notes typed up afterwards land at the end rather than jumping to the
     * front on a null.
     *
     * @return Collection<int, PlaytestObservation>
     */
    public function observationsOf(PlaytestSession $session): Collection
    {
        return $session->observations()
            ->with(['participant', 'creator'])
            ->orderByRaw('COALESCE(observed_at, created_at)')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Find one of a session's observations by id.
     */
    public function findObservationInSession(PlaytestSession $session, string $observationId): ?PlaytestObservation
    {
        $observation = $session->observations()->whereKey($observationId)->first();

        return $observation === null ? null : $observation->setRelation('session', $session);
    }

    /**
     * What participants said about a session, oldest first.
     *
     * @return Collection<int, PlaytestFeedback>
     */
    public function feedbackOf(PlaytestSession $session): Collection
    {
        return $session->feedback()
            ->with(['participant', 'creator'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Find one of a session's pieces of feedback by id.
     */
    public function findFeedbackInSession(PlaytestSession $session, string $feedbackId): ?PlaytestFeedback
    {
        $feedback = $session->feedback()->whereKey($feedbackId)->first();

        return $feedback === null ? null : $feedback->setRelation('session', $session);
    }

    /**
     * How many sittings a playtest has.
     */
    public function countSessionsOf(Playtest $playtest): int
    {
        return $playtest->sessions()->count();
    }

    /**
     * Determine whether a playtest has anything to show for itself.
     */
    public function hasSessions(Playtest $playtest): bool
    {
        return $playtest->sessions()->exists();
    }

    /**
     * Everything a session produced, counted in one trip.
     *
     * @return array{participants: int, observations: int, feedback: int}
     */
    public function tallyOf(PlaytestSession $session): array
    {
        return [
            'participants' => $session->participants()->count(),
            'observations' => $session->observations()->count(),
            'feedback' => $session->feedback()->count(),
        ];
    }

    /**
     * Gather everything a playtest has produced.
     *
     * The durations are worked out in PHP from the session timestamps rather
     * than in SQL. Date arithmetic is one of the few things that genuinely
     * differs between PostgreSQL and the SQLite the test suite runs on, and a
     * summary that is subtly wrong on one of them is worse than a few extra
     * rows crossing the wire — of which there are, by construction, very few.
     */
    public function summarise(Playtest $playtest): PlaytestSummary
    {
        $sessions = $playtest->sessions()->get();

        if ($sessions->isEmpty()) {
            return PlaytestSummary::empty($playtest);
        }

        $sessionIds = $sessions->modelKeys();

        $durations = $sessions
            ->map(fn (PlaytestSession $session): ?SessionDuration => $session->duration())
            ->filter()
            ->values();

        $totalSeconds = $durations->sum(fn (SessionDuration $duration): int => $duration->seconds);

        $ratings = PlaytestFeedback::query()
            ->whereIn('session_id', $sessionIds)
            ->whereNotNull('rating')
            ->pluck('rating');

        return new PlaytestSummary(
            playtest: $playtest,
            sessionCount: $sessions->count(),
            completedSessionCount: $sessions
                ->filter(fn (PlaytestSession $session): bool => $session->status === PlaytestSessionStatus::Completed)
                ->count(),
            cancelledSessionCount: $sessions
                ->filter(fn (PlaytestSession $session): bool => $session->status === PlaytestSessionStatus::Cancelled)
                ->count(),
            participantCount: PlaytestParticipant::query()->whereIn('session_id', $sessionIds)->count(),
            playerCount: PlaytestParticipant::query()
                ->whereIn('session_id', $sessionIds)
                ->where('role', PlaytestParticipantRole::Player)
                ->count(),
            observationCount: PlaytestObservation::query()->whereIn('session_id', $sessionIds)->count(),
            feedbackCount: PlaytestFeedback::query()->whereIn('session_id', $sessionIds)->count(),
            ratedFeedbackCount: $ratings->count(),
            averageRating: $ratings->isEmpty()
                ? null
                : round((float) $ratings->average(), 2),
            totalDuration: $durations->isEmpty() ? null : SessionDuration::fromSeconds((int) $totalSeconds),
            averageSessionDuration: $durations->isEmpty()
                ? null
                : SessionDuration::fromSeconds((int) round($totalSeconds / $durations->count())),
        );
    }

    /**
     * Match a term against a playtest's title, objective and hypothesis.
     *
     * Case folded on both sides so that searching "scoring" finds "Scoring is
     * unclear" on every database the application runs on, rather than only on
     * the ones whose collation happens to be insensitive.
     *
     * @param  Builder<Playtest>  $query
     * @return Builder<Playtest>
     */
    private function applySearch(Builder $query, string $term): Builder
    {
        $pattern = '%'.mb_strtolower(str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term)).'%';

        return $query->where(function (Builder $query) use ($pattern): void {
            $query
                ->whereRaw('LOWER(title) LIKE ? ESCAPE ?', [$pattern, '\\'])
                ->orWhereRaw('LOWER(objective) LIKE ? ESCAPE ?', [$pattern, '\\'])
                ->orWhereRaw("LOWER(COALESCE(hypothesis, '')) LIKE ? ESCAPE ?", [$pattern, '\\']);
        });
    }

    /**
     * Give every playtest in a game-scoped list the same game object.
     *
     * Each playtest is rendered with what the caller may do to it, and every
     * one of those answers needs the caller's access to the game. Left alone,
     * Eloquent would lazily load a separate game per playtest and resolve the
     * same workspace membership once per playtest.
     *
     * They all belong to the game that was passed in — that is what scoping
     * the query means — so handing them the one instance already in hand
     * collapses the whole list onto a single membership lookup, which the
     * workspace model then memoises.
     *
     * @param  Collection<int, Playtest>  $playtests
     * @return Collection<int, Playtest>
     */
    private function withGame(Game $game, Collection $playtests): Collection
    {
        return $playtests->each(fn (Playtest $playtest) => $playtest->setRelation('game', $game));
    }
}
