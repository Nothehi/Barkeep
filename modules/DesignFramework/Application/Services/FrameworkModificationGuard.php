<?php

namespace Modules\DesignFramework\Application\Services;

use Modules\DesignFramework\Domain\Exceptions\FrameworkIsNotModifiable;
use Modules\DesignFramework\Domain\Exceptions\FrameworkVersionIsNotAdoptable;
use Modules\DesignFramework\Domain\Exceptions\FrameworkVersionIsPublished;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\PhaseContent;

/**
 * The one place "may this still change?" is answered.
 *
 * Section 47 calls published-version immutability a critical invariant and says
 * it must not be enforced only in React. This is where it is enforced instead.
 * The policy stops the *request*; this stops the *operation*, so a caller
 * arriving another way — a console command, a seeder, a queued job, a later
 * module — is refused on exactly the same terms.
 *
 * Having one implementation is the whole point. "A published version is
 * read-only" spread across thirty commands is thirty chances to forget it in the
 * thirty-first, and the thirty-first is the one that quietly rewrites a criterion
 * a studio already answered.
 *
 * Two questions run through everything here, always in this order:
 *
 * 1. is the *framework* still alive? An archived framework freezes its versions
 *    with it, however draft they were.
 * 2. is the *version* still a draft? Publishing is what freezes content, and it
 *    freezes all of it — phases, principles, criteria, practices, prompts,
 *    checklists and checklist items alike.
 *
 * Content is checked through its version rather than by carrying a status of its
 * own that could disagree. A criterion does not know whether it may be edited;
 * the version it belongs to does.
 */
final class FrameworkModificationGuard
{
    /**
     * Require that a framework's own record may still be rewritten.
     *
     * Its name, address and description. Not its versions — see
     * {@see ensureFrameworkAcceptsNewVersions()}, which is looser on purpose.
     *
     * @throws FrameworkIsNotModifiable
     */
    public function ensureFrameworkIsModifiable(Framework $framework): void
    {
        if (! $framework->isModifiable()) {
            throw FrameworkIsNotModifiable::forStatus($framework->status);
        }
    }

    /**
     * Require that a framework may still gain a new edition.
     *
     * Looser than the check above by exactly one status, and that is why the two
     * exist separately: a published framework refuses changes to its own record
     * and accepts new draft versions. That combination *is* the mechanism by which
     * a methodology evolves — without it, publishing a framework would end it.
     *
     * @throws FrameworkIsNotModifiable
     */
    public function ensureFrameworkAcceptsNewVersions(Framework $framework): void
    {
        if (! $framework->acceptsNewVersions()) {
            throw FrameworkIsNotModifiable::forStatus($framework->status);
        }
    }

    /**
     * Require that a version, and everything inside it, may still change.
     *
     * The framework is checked first: if the whole methodology has been retired,
     * saying so is more useful than complaining about the version inside it.
     *
     * @throws FrameworkVersionIsPublished
     */
    public function ensureVersionIsModifiable(FrameworkVersion $version): void
    {
        $framework = $version->framework;

        if ($framework !== null && ! $framework->acceptsNewVersions()) {
            throw FrameworkVersionIsPublished::becauseFrameworkIsArchived();
        }

        if (! $version->isModifiable()) {
            throw FrameworkVersionIsPublished::andReadOnly();
        }
    }

    /**
     * Require that a phase may still be edited or reordered.
     *
     * @throws FrameworkVersionIsPublished
     */
    public function ensurePhaseIsModifiable(DesignPhaseDefinition $phase): void
    {
        $this->ensureOwningVersionIsModifiable($phase->version);
    }

    /**
     * Require that a principle, criterion, practice, prompt or checklist may still
     * be edited or reordered.
     *
     * @throws FrameworkVersionIsPublished
     */
    public function ensureContentIsModifiable(PhaseContent $content): void
    {
        $this->ensureOwningVersionIsModifiable($content->version);
    }

    /**
     * Require that a checklist item may still be edited or reordered.
     *
     * Reached through its checklist, which is the only content type that owns
     * children. An item has no version of its own to ask.
     *
     * @throws FrameworkVersionIsPublished
     */
    public function ensureChecklistItemIsModifiable(ChecklistItem $item): void
    {
        $this->ensureChecklistIsModifiable($item->checklist);
    }

    /**
     * Require that a checklist may still gain or reorder items.
     *
     * @throws FrameworkVersionIsPublished
     */
    public function ensureChecklistIsModifiable(?Checklist $checklist): void
    {
        if ($checklist === null) {
            return;
        }

        $this->ensureContentIsModifiable($checklist);
    }

    /**
     * Require that a version may be adopted by a game.
     *
     * The mirror image of every check above. Content is writable exactly while the
     * version is a draft, and adoptable exactly while it is published — the two
     * are deliberately disjoint, because a version whose questions could change
     * while games answered them would defeat the point of having versions.
     *
     * @throws FrameworkVersionIsNotAdoptable
     */
    public function ensureVersionIsAdoptable(FrameworkVersion $version): void
    {
        if (! $version->allowsAdoption()) {
            throw FrameworkVersionIsNotAdoptable::forStatus($version->status);
        }

        $framework = $version->framework;

        if ($framework !== null && ! $framework->isPublished()) {
            throw FrameworkVersionIsNotAdoptable::becauseFrameworkIsArchived();
        }
    }

    /**
     * Require that the version owning some content is still a draft.
     *
     * A null version means the relation was not loaded and the row is orphaned,
     * which cannot happen through a foreign key. Treated as permitted rather than
     * refused, because inventing a refusal from missing data would turn a loading
     * mistake into a mysterious 409.
     *
     * @throws FrameworkVersionIsPublished
     */
    private function ensureOwningVersionIsModifiable(?FrameworkVersion $version): void
    {
        if ($version !== null) {
            $this->ensureVersionIsModifiable($version);
        }
    }
}
