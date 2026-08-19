<?php

namespace Modules\PrototypeIteration\Application\Services;

use Modules\GameDesign\Application\Services\GameModificationGuard;
use Modules\GameDesign\Domain\Exceptions\GameIsNotModifiable;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Domain\Exceptions\DecisionIsNotModifiable;
use Modules\PrototypeIteration\Domain\Exceptions\ExperimentIsNotModifiable;
use Modules\PrototypeIteration\Domain\Exceptions\IterationIsNotModifiable;
use Modules\PrototypeIteration\Domain\Exceptions\PrototypeIsNotModifiable;
use Modules\PrototypeIteration\Domain\Exceptions\PrototypeVersionIsInUse;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\PrototypeRepository;

/**
 * The one place "may this still change?" is answered.
 *
 * Four things can freeze design work, and every write has to clear all of them:
 * the workspace may have stopped accepting changes, the game may have been
 * archived, the prototype or iteration may be closed, and — the rule unique to
 * this module — the prototype version may already have been built upon. The
 * first two are not this module's business, so they are delegated to GameDesign's
 * own guard rather than reimplemented, which stops "an archived game is
 * read-only" from having a second definition here that drifts from the first.
 *
 * The policies check all of this before a request reaches a command, but a policy
 * only guards the HTTP door. Every command runs this too, so a caller arriving
 * another way — a console command, a queued job, a later module — cannot write to
 * design history the product considers settled.
 *
 * Having exactly one implementation is the point. "Completed iterations are
 * read-only" spread across a dozen commands is a dozen chances to forget it in
 * the thirteenth, and this module has more writable child records than any other
 * in the platform.
 */
final class DesignWorkGuard
{
    public function __construct(
        private readonly GameModificationGuard $games,
        private readonly PrototypeRepository $prototypes,
    ) {}

    /**
     * Require that the given game may still have design work recorded against it.
     *
     * @throws GameIsNotModifiable
     */
    public function ensureGameAcceptsDesignWork(Game $game): void
    {
        $this->games->ensureGameIsModifiable($game);
    }

    /**
     * Require that the given prototype's own details may still be changed.
     *
     * The game is checked first: if the project is closed, saying so is more
     * useful than complaining about the prototype inside it.
     *
     * @throws PrototypeIsNotModifiable
     */
    public function ensurePrototypeIsModifiable(Prototype $prototype): void
    {
        $this->ensureGameOfPrototypeIsOpen($prototype);

        if (! $prototype->isModifiable()) {
            throw PrototypeIsNotModifiable::forStatus($prototype->status);
        }
    }

    /**
     * Require that the given prototype may still gain a new state.
     *
     * The same window as the prototype being editable today. Kept separate
     * because the two questions will come apart: an archived prototype that
     * refused edits but still accepted versions would be incoherent now, and a
     * later "frozen but maintained" state would need exactly this distinction.
     *
     * @throws PrototypeIsNotModifiable
     */
    public function ensurePrototypeAcceptsVersions(Prototype $prototype): void
    {
        $this->ensureGameOfPrototypeIsOpen($prototype);

        if (! $prototype->acceptsVersions()) {
            throw PrototypeIsNotModifiable::forStatus($prototype->status);
        }
    }

    /**
     * Require that a prototype state has not yet been built upon.
     *
     * The immutability rule, and the only guard in the platform that consults a
     * count rather than a status. A prototype version becomes frozen not because
     * somebody closed it but because something now depends on what it said — an
     * iteration pointing at it has recorded that this was the build on the table,
     * and editing it afterwards rewrites that record.
     *
     * Artifacts are deliberately not part of this. Attaching a print sheet to v3
     * documents what v3 was; it does not change it, so a used version stays open
     * to evidence about itself while refusing edits to itself.
     *
     * @throws PrototypeVersionIsInUse
     */
    public function ensurePrototypeVersionIsUnused(PrototypeVersion $version): void
    {
        $prototype = $version->prototype;

        if ($prototype !== null) {
            $this->ensurePrototypeIsModifiable($prototype);
        }

        $usage = $this->prototypes->countIterationsOfVersion($version);

        if ($usage > 0) {
            throw PrototypeVersionIsInUse::forVersion($version->getKey(), $usage);
        }
    }

    /**
     * Require that a prototype state may still gain artifacts.
     *
     * Looser than the check above by design — see the note there. A used version
     * accepts files about itself; an archived prototype accepts nothing.
     *
     * @throws PrototypeIsNotModifiable
     */
    public function ensurePrototypeVersionAcceptsArtifacts(PrototypeVersion $version): void
    {
        $prototype = $version->prototype;

        if ($prototype !== null) {
            $this->ensurePrototypeIsModifiable($prototype);
        }
    }

    /**
     * Require that the given iteration's plan may still be rewritten.
     *
     * @throws IterationIsNotModifiable
     */
    public function ensureIterationIsModifiable(Iteration $iteration): void
    {
        $this->ensureGameOfIterationIsOpen($iteration);

        if (! $iteration->isModifiable()) {
            throw IterationIsNotModifiable::forStatus($iteration->status);
        }
    }

    /**
     * Require that design work may still be recorded against the iteration.
     *
     * The same window as the plan being editable, which is deliberate rather than
     * incidental: a change or a decision recorded against a finished cycle is one
     * nobody can date, and a cycle that stayed open to work after concluding
     * would make its own outcome unfalsifiable.
     *
     * @throws IterationIsNotModifiable
     */
    public function ensureIterationAcceptsWork(Iteration $iteration): void
    {
        $this->ensureGameOfIterationIsOpen($iteration);

        if (! $iteration->acceptsWork()) {
            throw IterationIsNotModifiable::forStatus($iteration->status);
        }
    }

    /**
     * Require that the given experiment's design may still be changed.
     *
     * @throws ExperimentIsNotModifiable
     */
    public function ensureExperimentIsModifiable(DesignExperiment $experiment): void
    {
        $this->ensureIterationOfExperimentIsOpen($experiment);

        if (! $experiment->isModifiable()) {
            throw ExperimentIsNotModifiable::forStatus($experiment->status);
        }
    }

    /**
     * Require that the given decision's wording may still be changed.
     *
     * @throws DecisionIsNotModifiable
     */
    public function ensureDecisionIsModifiable(DesignDecision $decision): void
    {
        $this->ensureIterationOfDecisionIsOpen($decision);

        if (! $decision->isModifiable()) {
            throw DecisionIsNotModifiable::forStatus($decision->status);
        }
    }

    /**
     * Require that the given decision may still be settled.
     *
     * Looser than rewording by exactly the deferred state, which is why the two
     * exist separately: a deferred decision refuses nothing about being taken up
     * again, and its text stays editable while it waits.
     *
     * @throws DecisionIsNotModifiable
     */
    public function ensureDecisionIsOpen(DesignDecision $decision): void
    {
        $this->ensureIterationOfDecisionIsOpen($decision);

        if (! $decision->isOpen()) {
            throw DecisionIsNotModifiable::forStatus($decision->status);
        }
    }

    /**
     * Require that the game a prototype belongs to is still open.
     */
    private function ensureGameOfPrototypeIsOpen(Prototype $prototype): void
    {
        $game = $prototype->game;

        if ($game !== null) {
            $this->games->ensureGameIsModifiable($game);
        }
    }

    /**
     * Require that the game an iteration belongs to is still open.
     */
    private function ensureGameOfIterationIsOpen(Iteration $iteration): void
    {
        $game = $iteration->game;

        if ($game !== null) {
            $this->games->ensureGameIsModifiable($game);
        }
    }

    /**
     * Require that the cycle an experiment belongs to is still open.
     *
     * Reported as an experiment problem even though the iteration is what
     * refused, because the caller was acting on an experiment and that is the
     * object they are looking at. The message comes from the iteration, so they
     * are still told the real reason.
     */
    private function ensureIterationOfExperimentIsOpen(DesignExperiment $experiment): void
    {
        $iteration = $experiment->iteration;

        if ($iteration === null) {
            return;
        }

        $this->ensureGameOfIterationIsOpen($iteration);

        if (! $iteration->acceptsWork()) {
            throw ExperimentIsNotModifiable::becauseIterationIsClosed($iteration->status->deniedReason());
        }
    }

    /**
     * Require that the cycle a decision belongs to is still open.
     */
    private function ensureIterationOfDecisionIsOpen(DesignDecision $decision): void
    {
        $iteration = $decision->iteration;

        if ($iteration === null) {
            return;
        }

        $this->ensureGameOfIterationIsOpen($iteration);

        if (! $iteration->acceptsWork()) {
            throw DecisionIsNotModifiable::becauseIterationIsClosed($iteration->status->deniedReason());
        }
    }
}
