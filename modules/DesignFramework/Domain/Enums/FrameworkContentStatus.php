<?php

namespace Modules\DesignFramework\Domain\Enums;

/**
 * Whether one piece of framework content counts.
 *
 * Phases, principles, criteria, practices, prompts and checklists each carry
 * this. It is not a second lifecycle — content never transitions on its own,
 * and there are no publish or archive commands for it. It is set when the
 * content is written or edited, and it exists so that a framework author
 * working on a draft version can leave something half-finished in place
 * without it appearing in a phase page or counting towards a game's progress.
 *
 * The version's own status is what actually freezes content; this only decides
 * whether a published version *shows* a given row. Which is why archiving a
 * criterion in a draft is a normal edit, and why nothing here is reachable
 * once the version is published.
 */
enum FrameworkContentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * The status content starts life in.
     *
     * Draft, so that adding a phase to a version does not immediately change
     * what everybody reading the builder sees as finished.
     */
    public static function default(): self
    {
        return self::Draft;
    }

    /**
     * Determine whether designers following the framework should see this.
     *
     * Draft content is visible in the builder — that is where it is being
     * written — and absent from the game-facing phase pages. Archived content
     * is absent from both, but the row survives so that games which already
     * evaluated a criterion keep their answers.
     */
    public function isVisibleToDesigners(): bool
    {
        return $this === self::Published;
    }

    /**
     * Determine whether this content counts towards a game's progress.
     *
     * The same rule, named separately because it is asked for a different
     * reason and could reasonably diverge later: a framework author might one
     * day want optional published content that is shown but not counted.
     */
    public function countsTowardsProgress(): bool
    {
        return $this === self::Published;
    }

    /**
     * A human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Published => __('Published'),
            self::Archived => __('Archived'),
        };
    }
}
