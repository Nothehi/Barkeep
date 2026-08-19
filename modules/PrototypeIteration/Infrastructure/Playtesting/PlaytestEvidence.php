<?php

namespace Modules\PrototypeIteration\Infrastructure\Playtesting;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Queries\GetFeedbackInGame;
use Modules\Playtesting\Application\Queries\GetObservationInGame;
use Modules\Playtesting\Application\Queries\GetPlaytest;
use Modules\Playtesting\Application\Queries\GetPlaytests;
use Modules\Playtesting\Application\Queries\GetPlaytestSummary;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\PrototypeIteration\Domain\Enums\EvidenceType;
use Modules\PrototypeIteration\Domain\Exceptions\PlaytestDoesNotBelongToGame;
use Modules\PrototypeIteration\Domain\Models\IterationPlaytest;
use Modules\PrototypeIteration\Domain\ValueObjects\PlaytestReference;

/**
 * The only file in this module that knows Playtesting exists.
 *
 * An iteration is judged on evidence, and Playtesting owns the evidence. This is
 * the seam between the two, and the shape of it is the whole point: nothing
 * crosses it except ids going in and this module's own
 * {@see PlaytestReference} value objects coming out. No observation, no piece of
 * feedback and no participant is ever copied here, and no Playtesting model
 * escapes this class — an architecture test holds both halves of that.
 *
 * Everything is read through Playtesting's published application queries rather
 * than through queries of its own. That is what keeps "which playtests belong to
 * this game" and "what has this playtest produced" as single definitions, and it
 * is why the counts on an iteration screen are the same counts the playtest's
 * own screen shows rather than a second opinion computed here.
 *
 * ## Why a reference and not the model
 *
 * Handing back the model would be less code and would leak the boundary
 * immediately: every controller, resource and command that touched a link would
 * be a place where Playtesting's schema could be depended upon, and the module
 * would be pinned to it. A reference carries the six facts an iteration screen
 * needs and nothing else, so Playtesting can reshape everything behind it.
 *
 * ## Scoping
 *
 * Every lookup takes a resolved `Game`, and resolution goes through it. A
 * playtest id from another studio's game does not resolve — it is not compared
 * and refused — so a playtest id in a request body cannot be used to reach
 * across a workspace boundary or to discover that a game exists.
 */
final class PlaytestEvidence
{
    public function __construct(
        private readonly GetPlaytest $playtest,
        private readonly GetPlaytests $playtests,
        private readonly GetPlaytestSummary $summary,
        private readonly GetObservationInGame $observation,
        private readonly GetFeedbackInGame $feedback,
    ) {}

    /**
     * Prove that a playtest id names one of this game's playtests, or fail.
     *
     * Called before a link is written. The return is the id rather than the
     * playtest, deliberately: the caller is about to store an id, and handing it
     * a model would invite it to store something else from it.
     *
     * @throws PlaytestDoesNotBelongToGame when the playtest is not this game's
     */
    public function requirePlaytestOf(Game $game, string $playtestId): string
    {
        $playtest = $this->resolve($game, $playtestId);

        if ($playtest === null) {
            throw PlaytestDoesNotBelongToGame::forPair($game->getKey(), $playtestId);
        }

        return (string) $playtest->getKey();
    }

    /**
     * Determine whether a playtest id names one of this game's playtests.
     *
     * Used by validation, which wants to report the problem next to the field
     * rather than to raise it.
     */
    public function gameHasPlaytest(Game $game, string $playtestId): bool
    {
        return $this->resolve($game, $playtestId) !== null;
    }

    /**
     * Turn an iteration's links into something an interface can render.
     *
     * The counts are read per playtest rather than cached on the link row, which
     * is the deliberate trade described on {@see PlaytestReference}: an
     * iteration has a handful of playtests, so a handful of aggregate queries is
     * affordable, and a stored count would start disagreeing with the playtest's
     * own screen the first time somebody added a session.
     *
     * A link whose playtest cannot be read renders as unavailable rather than
     * being dropped. "This iteration cited evidence you cannot see" is true and
     * useful; a silently shorter list reads as "this iteration cited nothing".
     *
     * @param  iterable<int, IterationPlaytest>  $links
     * @return Collection<int, PlaytestReference>
     */
    public function referencesFor(Game $game, iterable $links): Collection
    {
        return Collection::make($links)->map(
            fn (IterationPlaytest $link): PlaytestReference => $this->referenceFor($game, $link),
        )->values();
    }

    /**
     * Turn a single link into a reference.
     */
    public function referenceFor(Game $game, IterationPlaytest $link): PlaytestReference
    {
        $playtest = $this->resolve($game, $link->playtest_id);

        if ($playtest === null) {
            return PlaytestReference::unavailable($link->getKey(), $link->playtest_id);
        }

        $summary = $this->summary->handle($playtest);

        return new PlaytestReference(
            linkId: $link->getKey(),
            playtestId: (string) $playtest->getKey(),
            title: $playtest->title,
            status: $playtest->status->value,
            statusLabel: $playtest->status->label(),
            attachedAt: $link->created_at?->toDateTimeImmutable(),
            sessionCount: $summary->sessionCount,
            participantCount: $summary->participantCount,
            observationCount: $summary->observationCount,
            feedbackCount: $summary->feedbackCount,
            totalDurationSeconds: $summary->totalDuration?->seconds,
        );
    }

    /**
     * The game's playtests, as the "attach a playtest" picker sees them.
     *
     * Deliberately the whole list rather than "the ones not yet attached". The
     * screen knows which links it already holds and can grey those out, and
     * filtering here would mean this adapter needed to know about the iteration
     * doing the asking — which is one more thing across the seam for no gain.
     *
     * @return Collection<int, array{id: string, title: string, status: string, status_label: string}>
     */
    public function selectableFor(Game $game): Collection
    {
        return Collection::make($this->playtests->handle($game)->all())
            ->map(fn (Playtest $playtest): array => $this->selectableRow($playtest))
            ->values();
    }

    /**
     * One row of the playtest picker.
     *
     * Its own method so the shape is declared once, in terms this module owns: a status is a string
     * here rather than one of Playtesting's four specific values, because the picker renders whatever
     * it is given and nothing on this side branches on it.
     *
     * @return array{id: string, title: string, status: string, status_label: string}
     */
    private function selectableRow(Playtest $playtest): array
    {
        return [
            'id' => (string) $playtest->getKey(),
            'title' => $playtest->title,
            'status' => $playtest->status->value,
            'status_label' => $playtest->status->label(),
        ];
    }

    /**
     * How many pieces of evidence a set of links accounts for.
     *
     * Counted rather than stored, for the reason every figure in this platform
     * is counted rather than stored: the moment a stored total and the rows it
     * describes can disagree, somebody spends an afternoon finding out which one
     * is lying.
     *
     * @param  iterable<int, IterationPlaytest>  $links
     * @return array{playtests: int, sessions: int, observations: int, feedback: int}
     */
    public function tallyFor(Game $game, iterable $links): array
    {
        $references = $this->referencesFor($game, $links);

        return [
            'playtests' => $references->count(),
            'sessions' => (int) $references->sum(fn (PlaytestReference $r): int => $r->sessionCount),
            'observations' => (int) $references->sum(fn (PlaytestReference $r): int => $r->observationCount),
            'feedback' => (int) $references->sum(fn (PlaytestReference $r): int => $r->feedbackCount),
        ];
    }

    /**
     * Determine whether an observation id names one of this game's observations.
     *
     * Resolved through Playtesting's own game-scoped query, which is what makes a
     * citation of an observation safe despite the reference having no foreign key
     * behind it: an id from another studio's playtest does not resolve, and is
     * reported the same way as an id that names nothing.
     */
    public function gameHasObservation(Game $game, string $observationId): bool
    {
        return $this->isUuid($observationId)
            && $this->observation->handle($game, $observationId) !== null;
    }

    /**
     * Determine whether a feedback id names one of this game's feedback entries.
     */
    public function gameHasFeedback(Game $game, string $feedbackId): bool
    {
        return $this->isUuid($feedbackId)
            && $this->feedback->handle($game, $feedbackId) !== null;
    }

    /**
     * Read the words a citation points at, live, at the moment of rendering.
     *
     * The half of the evidence UI that makes a decision worth reading: not "this
     * cites observation 9f0c…", but "Players spent less time waiting". Read fresh
     * on every render rather than copied at citation time, so a correction to an
     * observation appears in every decision that cited it — where a stored copy
     * would leave a decision quoting words the observation no longer contains.
     *
     * Returns null for anything that does not resolve, which the caller turns into
     * a visibly missing citation rather than a silently shorter list.
     *
     * @return array{excerpt: string, attribution: ?string, playtest_id: ?string}|null
     */
    public function excerptFor(Game $game, EvidenceType $type, string $referenceId): ?array
    {
        if (! $this->isUuid($referenceId)) {
            return null;
        }

        return match ($type) {
            EvidenceType::Playtest => $this->playtestExcerpt($game, $referenceId),
            EvidenceType::Observation => $this->observationExcerpt($game, $referenceId),
            EvidenceType::Feedback => $this->feedbackExcerpt($game, $referenceId),
            default => null,
        };
    }

    /**
     * The excerpt for a whole playtest: what it set out to find out.
     *
     * The objective rather than the conclusion, because a citation of a playtest is
     * a citation of the investigation — and the objective is what identifies which
     * one somebody means.
     *
     * @return array{excerpt: string, attribution: ?string, playtest_id: ?string}|null
     */
    private function playtestExcerpt(Game $game, string $playtestId): ?array
    {
        $playtest = $this->resolve($game, $playtestId);

        if ($playtest === null) {
            return null;
        }

        return [
            'excerpt' => $playtest->title,
            'attribution' => $playtest->objective,
            'playtest_id' => (string) $playtest->getKey(),
        ];
    }

    /**
     * The excerpt for an observation: what was noticed, and about whom.
     *
     * @return array{excerpt: string, attribution: ?string, playtest_id: ?string}|null
     */
    private function observationExcerpt(Game $game, string $observationId): ?array
    {
        $observation = $this->observation->handle($game, $observationId);

        if ($observation === null) {
            return null;
        }

        return [
            'excerpt' => $observation->content,
            'attribution' => $observation->participant?->display_name,
            'playtest_id' => $observation->session?->playtest_id,
        ];
    }

    /**
     * The excerpt for a piece of feedback: what was said, and by whom.
     *
     * The attribution falls back to nothing rather than to "anonymous", because
     * feedback from somebody who was not named in the participant list is a real
     * and common case, and the wording of that belongs to the interface.
     *
     * @return array{excerpt: string, attribution: ?string, playtest_id: ?string}|null
     */
    private function feedbackExcerpt(Game $game, string $feedbackId): ?array
    {
        $feedback = $this->feedback->handle($game, $feedbackId);

        if ($feedback === null) {
            return null;
        }

        return [
            'excerpt' => $feedback->content,
            'attribution' => $feedback->participant?->display_name,
            'playtest_id' => $feedback->session?->playtest_id,
        ];
    }

    /**
     * Resolve a playtest through the game that owns it.
     *
     * The uuid guard in front of the query is not cosmetic: PostgreSQL raises
     * rather than returning nothing when a uuid column is compared against a
     * string that is not one, which would turn a mistyped id into a 500.
     */
    private function resolve(Game $game, string $playtestId): ?Playtest
    {
        return $this->isUuid($playtestId)
            ? $this->playtest->handle($game, $playtestId)
            : null;
    }

    /**
     * Determine whether a string could be one of the platform's identifiers.
     *
     * Guards every lookup in this class, and it is not cosmetic: PostgreSQL raises
     * rather than returning nothing when a uuid column is compared against a string
     * that is not one, which would turn a mistyped id into a 500 instead of a
     * "cannot be found".
     */
    private function isUuid(string $value): bool
    {
        return Str::isUuid($value);
    }
}
