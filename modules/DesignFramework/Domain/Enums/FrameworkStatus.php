<?php

namespace Modules\DesignFramework\Domain\Enums;

/**
 * Where a framework, or one version of one, sits in its own life.
 *
 * Shared by both deliberately. A framework and its versions move through the
 * same three states for the same reason — being written, being followed, being
 * retired — and giving them one enum means "published is read-only" has one
 * definition instead of two that can drift.
 *
 * The transitions below are the whole lifecycle. Encoding them here rather
 * than in a controller is what makes "a published version cannot go back to
 * draft" a property of the domain instead of an omission somebody has to
 * remember.
 *
 * Publishing is the load-bearing move. For a version it is irreversible in
 * substance as well as in status: it is the moment the version's content
 * freezes, because games may now adopt it and their answers must keep pointing
 * at the questions they were given. See `FrameworkVersionGuard`.
 */
enum FrameworkStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * The status a framework and a version start life in.
     */
    public static function default(): self
    {
        return self::Draft;
    }

    /**
     * The statuses this one may legally move to.
     *
     * There is no route back from Published to Draft, and that omission is the
     * point. Unpublishing a version would let its phases, criteria and
     * checklists change underneath every game already following it, which is
     * exactly what versioning exists to prevent. The way to change a published
     * methodology is to create the next version.
     *
     * Archived is terminal. An archived framework is still readable — games
     * that adopted its versions keep working — but nothing new happens to it.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Draft => [self::Published, self::Archived],
            self::Published => [self::Archived],
            self::Archived => [],
        };
    }

    /**
     * Determine whether this status may move to the given one.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), strict: true);
    }

    /**
     * Determine whether the record's own fields may still be edited.
     *
     * This answers for the framework or version *row* — its name, its
     * description. For a version it is also the answer for everything inside
     * it, which is the immutability rule in section 47: once a version is
     * published, its phases and content are frozen with it.
     */
    public function allowsModification(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Determine whether games may adopt this.
     *
     * Only published versions. A draft is still being written, so a game
     * following it would find its questions changing between visits; an
     * archived one is retired, and starting a new project on a retired
     * methodology is a mistake worth refusing rather than allowing quietly.
     */
    public function allowsAdoption(): bool
    {
        return $this === self::Published;
    }

    /**
     * Determine whether the record is readable by anybody who may see
     * frameworks at all.
     *
     * Drafts are not. A half-written methodology visible to every designer on
     * the platform would be read as advice, and it is not advice yet — so
     * drafts are visible only to the people who administer frameworks.
     */
    public function isPubliclyVisible(): bool
    {
        return $this !== self::Draft;
    }

    /**
     * Determine whether the record has any life left in it.
     */
    public function isTerminal(): bool
    {
        return $this->transitions() === [];
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

    /**
     * The verb offered for moving from this status to the given one.
     *
     * Lifecycle changes are explicit actions rather than a free choice from a
     * dropdown, so the wording of each one belongs beside the matrix that
     * allows it and the client renders whatever the server offers.
     */
    public function transitionLabelTo(self $target): string
    {
        return match ($target) {
            self::Draft => __('Return to draft'),
            self::Published => __('Publish'),
            self::Archived => __('Archive'),
        };
    }

    /**
     * The message shown when a change is refused because of this status.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Draft => __('This is still a draft.'),
            self::Published => __('This has been published and is read-only. Create a new version to make changes.'),
            self::Archived => __('This has been archived and is read-only.'),
        };
    }
}
