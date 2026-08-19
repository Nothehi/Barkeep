<?php

namespace Modules\PrototypeIteration\Domain\Enums;

/**
 * What an iteration turned out to be worth.
 *
 * Required when an iteration completes, and the reason it is an enum rather
 * than free prose is that four words let a year of design history be read at a
 * glance — a column of outcomes down the side of an iteration list is the
 * shape of a project's progress.
 *
 * The summary beside it carries the actual content. This is the index, not the
 * account, which is why the list is short enough to scan and why
 * {@see Inconclusive} is a first-class answer rather than a failure: an
 * iteration that did not settle its question has still told the designer
 * something, and forcing it into "failed" would make the history lie.
 *
 * Nothing in the platform scores these or aggregates them into a health
 * figure. "Three partials in a row" means something to the person who ran
 * them and nothing that Barkeep is in a position to judge.
 */
enum IterationOutcome: string
{
    case Success = 'success';
    case Partial = 'partial';
    case Failed = 'failed';
    case Inconclusive = 'inconclusive';

    /**
     * A human readable label for the outcome.
     */
    public function label(): string
    {
        return match ($this) {
            self::Success => __('Success'),
            self::Partial => __('Partial'),
            self::Failed => __('Failed'),
            self::Inconclusive => __('Inconclusive'),
        };
    }

    /**
     * What choosing this outcome claims.
     *
     * Worded as the sentence a designer would say, because these four words
     * are picked in a hurry at the end of a cycle and the difference between
     * "failed" and "inconclusive" is the one people get wrong.
     */
    public function description(): string
    {
        return match ($this) {
            self::Success => __('The change did what it was meant to do.'),
            self::Partial => __('The change helped, but the problem is not solved.'),
            self::Failed => __('The change did not work, and we know why.'),
            self::Inconclusive => __('We could not tell either way from what we saw.'),
        };
    }
}
