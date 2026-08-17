<?php

namespace Modules\DesignFramework\Application\Services;

use Modules\DesignFramework\Domain\Exceptions\ContentDoesNotBelongToFrameworkVersion;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Domain\Models\PhaseContent;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\GameFrameworkRepository;

/**
 * The one place a game's framework work is matched to the content it is about.
 *
 * Every write in sections 22 to 25 has the same shape: a game arrives as a resolved route
 * binding, and the criterion, practice, checklist item or prompt arrives as an id in the URL.
 * Proving they belong together is this module's counterpart to Playtesting's game/version
 * invariant, and it happens here so that no command, controller or form request gets the chance
 * to skip it.
 *
 * ## Two halves, and why both exist
 *
 * **Resolution** (`criterion()`, `practice()`, `prompt()`, `checklistItem()`) looks an id up
 * *through* the version the game actually adopted. The proof is structural rather than a
 * comparison: content from v2 is not found at all for a game on v1, so there is no code path on
 * which a mismatched pair is compared, found wanting and rejected. This is what the route
 * bindings use, which is why a bad id 404s before any handler runs.
 *
 * **Confirmation** (`ensureAdopted()`, `ensureItemAdopted()`) re-checks an already-resolved
 * model against the adoption without touching the database. The commands run this, so a caller
 * arriving another way — a console command, a queued job, a later module — cannot record a game's
 * answer against a question its version never asked. The route binding guards the HTTP door;
 * this guards the operation.
 *
 * That distinction matters because framework content is not secret. A criterion belongs to a
 * globally published version, so knowing its id proves nothing about who is holding it. What
 * keeps one studio out of another's progress is that the adoption comes from the URL's game and
 * the content comes from the adoption.
 */
final class FrameworkContentLocator
{
    public function __construct(private readonly GameFrameworkRepository $adoptions) {}

    /**
     * Resolve one of the adopted version's criteria, or fail.
     *
     * @throws ContentDoesNotBelongToFrameworkVersion when the criterion is not this version's
     */
    public function criterion(GameFramework $adoption, string $criterionId): DesignCriterion
    {
        return $this->adoptions->findCriterionIn($adoption, $criterionId)
            ?? throw $this->mismatch($adoption, $criterionId);
    }

    /**
     * Resolve one of the adopted version's practices, or fail.
     *
     * @throws ContentDoesNotBelongToFrameworkVersion
     */
    public function practice(GameFramework $adoption, string $practiceId): DesignPractice
    {
        return $this->adoptions->findPracticeIn($adoption, $practiceId)
            ?? throw $this->mismatch($adoption, $practiceId);
    }

    /**
     * Resolve one of the adopted version's prompts, or fail.
     *
     * @throws ContentDoesNotBelongToFrameworkVersion
     */
    public function prompt(GameFramework $adoption, string $promptId): DesignPrompt
    {
        return $this->adoptions->findPromptIn($adoption, $promptId)
            ?? throw $this->mismatch($adoption, $promptId);
    }

    /**
     * Resolve one checklist item belonging to the adopted version, or fail.
     *
     * Reached through its checklist, because an item has no version of its own — its version is
     * its list's. One join more than the others, and the same guarantee.
     *
     * @throws ContentDoesNotBelongToFrameworkVersion
     */
    public function checklistItem(GameFramework $adoption, string $itemId): ChecklistItem
    {
        return $this->adoptions->findChecklistItemIn($adoption, $itemId)
            ?? throw $this->mismatch($adoption, $itemId);
    }

    /**
     * Confirm that already-resolved content belongs to the version the game adopted.
     *
     * No query: both objects are in hand, and comparing their version ids is the whole check.
     * That cheapness is why every command can afford to run it even though the route binding has
     * usually done the resolving already.
     *
     * @throws ContentDoesNotBelongToFrameworkVersion
     */
    public function ensureAdopted(GameFramework $adoption, PhaseContent $content): void
    {
        if ((string) $content->framework_version_id !== (string) $adoption->framework_version_id) {
            throw $this->mismatch($adoption, (string) $content->getKey());
        }
    }

    /**
     * Confirm that an already-resolved checklist item belongs to the adopted version.
     *
     * A missing checklist relation is treated as a mismatch rather than as permitted, which is
     * the opposite of how the module treats other unloaded relations. The reason is the stakes:
     * everywhere else a null relation means "cannot prove there is a problem", and here it means
     * "cannot prove there is not".
     *
     * @throws ContentDoesNotBelongToFrameworkVersion
     */
    public function ensureItemAdopted(GameFramework $adoption, ChecklistItem $item): void
    {
        $checklist = $item->checklist;

        if ($checklist === null) {
            throw $this->mismatch($adoption, (string) $item->getKey());
        }

        $this->ensureAdopted($adoption, $checklist);
    }

    /**
     * Build the refusal, naming the version rather than the game.
     *
     * Deliberately says nothing about whether the id exists elsewhere. "That does not belong to
     * the framework version this game is following" is true whether the id names v2's criterion
     * or nothing at all, and distinguishing the two would turn this into a way to probe what
     * other versions contain.
     */
    private function mismatch(GameFramework $adoption, string $contentId): ContentDoesNotBelongToFrameworkVersion
    {
        return ContentDoesNotBelongToFrameworkVersion::forPair(
            (string) $adoption->framework_version_id,
            $contentId,
        );
    }
}
