<?php

namespace Modules\DesignFramework\Application\Services;

use Illuminate\Database\Eloquent\Collection as Rows;
use Illuminate\Support\Collection as Items;
use Modules\DesignFramework\Application\DTOs\ChecklistProgress;
use Modules\DesignFramework\Application\DTOs\FrameworkProgress;
use Modules\DesignFramework\Application\DTOs\PhaseProgress;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Domain\Models\PhaseContent;
use Modules\DesignFramework\Domain\ValueObjects\ProgressRatio;
use Modules\DesignFramework\Infrastructure\GameDesign\DesignFacts;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\GameFrameworkRepository;
use Modules\GameDesign\Domain\Models\DesignRecord;

/**
 * The one place framework progress is worked out.
 *
 * Section 41 asks for this to be isolated so the weighting can evolve, and asks for it not to be
 * hardcoded across controllers and screens. Nothing else in the module — and nothing in the interface —
 * computes a percentage. The controllers hand out {@see FrameworkProgress}; the screens draw the bars
 * they are given.
 *
 * ## What counts
 *
 * Three things, exactly as section 41 states: evaluated criteria, completed practices, and ticked
 * *required* checklist items. Summed as counts rather than averaged as percentages, so a phase with
 * one criterion and twenty checklist items weights them by how much work each actually represents.
 *
 * ## What does not count, and why
 *
 * - **Principles.** There is nothing to do with a principle but hold it in mind. A bar that advanced
 *   when you ticked "I have read this" would measure reading.
 * - **Prompts.** Counted and reported, deliberately excluded from the total. A prompt has no right
 *   answer, so letting it move a percentage rewards typing over thinking. The count is produced
 *   because a phase page genuinely wants to say "3 of 5 answered".
 * - **The grade itself.** A criterion counts once it has been *assessed*, whatever the assessment was.
 *   Turning "strong" into more points than "weak" would make a designer who graded their game honestly
 *   look worse than one who did not, which is precisely backwards.
 * - **Optional checklist items.** Shown, tickable, not counted — which is what lets an author add a
 *   nice-to-have without everybody's numbers moving.
 * - **Draft and archived content.** Only published content counts, and only content filed under a
 *   published phase or under no phase at all. The two filters have to agree, or a phase's numbers
 *   would not add up to the version's.
 *
 * ## Why nothing is stored
 *
 * Every figure is counted on read. A stored percentage is a fourth fact that can disagree with the
 * three it came from, and the disagreement is only ever noticed later — usually by a designer who
 * cannot work out why their bar says eighty when two things are outstanding.
 */
final class FrameworkProgressCalculator
{
    public function __construct(
        private readonly FrameworkRepository $frameworks,
        private readonly GameFrameworkRepository $adoptions,
        private readonly DesignFacts $facts,
    ) {}

    /**
     * Work out how far a game has got through its framework.
     */
    public function for(GameFramework $adoption): FrameworkProgress
    {
        $version = $adoption->version;

        if ($version === null) {
            return $this->empty($adoption);
        }

        $phases = $this->frameworks->phasesOf($version);

        /*
         * The set of phases that count. Content filed under a draft or archived phase is excluded
         * everywhere — from the version totals as well as from the phase breakdown — because a figure
         * that appears in one and not the other is a figure nobody can reconcile.
         */
        $phaseIds = $phases
            ->map(fn (DesignPhaseDefinition $phase): string => (string) $phase->getKey())
            ->all();

        $criteria = $this->countable($this->frameworks->contentOf($version, DesignCriterion::class), $phaseIds);
        $practices = $this->countable($this->frameworks->contentOf($version, DesignPractice::class), $phaseIds);
        $prompts = $this->countable($this->frameworks->contentOf($version, DesignPrompt::class), $phaseIds);

        $checklists = $this->frameworks->checklistsWithItems($version)
            ->filter(fn (Checklist $checklist): bool => $this->counts($checklist, $phaseIds));

        /*
         * Checklist items are the one content type without a phase of their own, so they are attributed
         * to their checklist's phase. Flattened once here — a list of required items, plus a map from
         * item id to the phase its list sits in — rather than re-derived per phase.
         */
        $items = $checklists->flatMap(
            fn (Checklist $checklist): array => $checklist->items
                ->filter(fn (ChecklistItem $item): bool => $item->countsTowardsProgress())
                ->values()
                ->all(),
        );

        $itemPhases = [];

        foreach ($checklists as $checklist) {
            foreach ($checklist->items as $item) {
                $itemPhases[(string) $item->getKey()] = $checklist->phase_id === null
                    ? null
                    : (string) $checklist->phase_id;
            }
        }

        $evaluated = array_keys($this->adoptions->evaluatedCriterionIds($adoption));
        $donePractices = $this->adoptions->completedPracticeIds($adoption);
        $doneItems = $this->adoptions->completedItemIds($adoption);
        $answered = $this->adoptions->answeredPromptIds($adoption);

        /*
         * Content answered by the game's own design record counts as done without
         * anybody having graded or ticked it. A criterion asking whether the
         * player count is decided is answered by the player count being decided,
         * and the design record is where that lives.
         *
         * Folded into the same id lists as the manual answers rather than counted
         * separately, so there is still one definition of "done" per content type
         * and the phase and version figures cannot disagree about which it used.
         * The record is read once per calculation, not once per criterion.
         */
        $record = $adoption->game === null ? null : $this->facts->recordFor($adoption->game);

        $evaluated = array_merge($evaluated, $this->satisfied($criteria, $record));
        $doneItems = array_merge($doneItems, $this->satisfied($items, $record));

        $phaseProgress = $phases
            ->map(fn (DesignPhaseDefinition $phase): PhaseProgress => PhaseProgress::of(
                phaseId: (string) $phase->getKey(),
                slug: $phase->slug,
                name: $phase->name,
                position: $phase->position,
                criteria: $this->ratio($this->inPhase($criteria, (string) $phase->getKey()), $evaluated),
                practices: $this->ratio($this->inPhase($practices, (string) $phase->getKey()), $donePractices),
                checklistItems: $this->itemRatio(
                    $this->itemsInPhase($items, $itemPhases, (string) $phase->getKey()),
                    $doneItems,
                ),
                prompts: $this->ratio($this->inPhase($prompts, (string) $phase->getKey()), $answered),
            ))
            ->values()
            ->all();

        return FrameworkProgress::of(
            gameFrameworkId: (string) $adoption->getKey(),
            frameworkVersionId: (string) $version->getKey(),
            criteria: $this->ratio($criteria, $evaluated),
            practices: $this->ratio($practices, $donePractices),
            checklistItems: $this->itemRatio($items, $doneItems),
            prompts: $this->ratio($prompts, $answered),
            phaseProgress: $phaseProgress,
        );
    }

    /**
     * Work out how much of each of a game's checklists it has ticked.
     *
     * Reported separately from phase progress because a checklist is read as a unit — "2 / 4 completed"
     * above the boxes — and because the client needs a tick against each item, not just a total.
     *
     * @param  Rows<int, Checklist>  $checklists
     * @return array<int, ChecklistProgress>
     */
    public function forChecklists(GameFramework $adoption, Rows $checklists): array
    {
        $completed = $this->adoptions->completedItemIds($adoption);

        /*
         * The same folding as the version totals, and for the same reason: an item
         * met by a fact has to read as ticked here too, or the box beside "Player
         * count decided" would sit empty while the phase percentage counted it.
         */
        $record = $adoption->game === null ? null : $this->facts->recordFor($adoption->game);

        $completed = array_merge($completed, $this->satisfied(
            $checklists->flatMap(fn (Checklist $checklist): array => $checklist->items->all())->toBase(),
            $record,
        ));

        return $checklists
            ->map(function (Checklist $checklist) use ($completed): ChecklistProgress {
                $items = $checklist->items;

                $completions = $items
                    ->mapWithKeys(fn (ChecklistItem $item): array => [
                        (string) $item->getKey() => in_array((string) $item->getKey(), $completed, strict: true),
                    ])
                    ->all();

                $required = $items->filter(fn (ChecklistItem $item): bool => $item->countsTowardsProgress());

                return new ChecklistProgress(
                    checklist: $checklist,
                    required: ProgressRatio::of(
                        $required
                            ->filter(fn (ChecklistItem $item): bool => $completions[(string) $item->getKey()] ?? false)
                            ->count(),
                        $required->count(),
                    ),
                    all: ProgressRatio::of(count(array_filter($completions)), $items->count()),
                    completions: $completions,
                );
            })
            ->values()
            ->all();
    }

    /**
     * The ids of content the game's design record already answers.
     *
     * Only content that names a fact is considered, so the ordinary judgement
     * criteria are untouched — they are graded or they are outstanding, exactly as
     * before.
     *
     * @param  Items<int, PhaseContent>|Items<int, ChecklistItem>  $content
     * @return list<string>
     */
    private function satisfied(Items $content, ?DesignRecord $record): array
    {
        return $content
            ->filter(fn (PhaseContent|ChecklistItem $row): bool => $row->isAnsweredByTheDesignRecord()
                && $this->facts->recorded($record, (string) $row->satisfied_by))
            ->map(fn (PhaseContent|ChecklistItem $row): string => (string) $row->getKey())
            ->values()
            ->all();
    }

    /**
     * Drop content filed under a phase that does not count.
     *
     * Content with no phase is kept: it applies across the whole methodology and belongs in the
     * version's totals even though it appears on no phase page.
     *
     * @param  Rows<int, PhaseContent>  $content
     * @param  array<int, string>  $phaseIds
     * @return Items<int, PhaseContent>
     */
    private function countable(Rows $content, array $phaseIds): Items
    {
        return $content
            ->filter(fn (PhaseContent $row): bool => $this->counts($row, $phaseIds))
            ->values()
            ->toBase();
    }

    /**
     * Determine whether a row's phase is one that counts.
     *
     * @param  array<int, string>  $phaseIds
     */
    private function counts(PhaseContent $row, array $phaseIds): bool
    {
        return $row->phase_id === null
            || in_array((string) $row->phase_id, $phaseIds, strict: true);
    }

    /**
     * Narrow a content set to one phase.
     *
     * @param  Items<int, PhaseContent>  $content
     * @return Items<int, PhaseContent>
     */
    private function inPhase(Items $content, string $phaseId): Items
    {
        return $content->filter(fn (PhaseContent $row): bool => (string) $row->phase_id === $phaseId);
    }

    /**
     * Count how much of a content set has been dealt with.
     *
     * @param  Items<int, PhaseContent>  $content
     * @param  array<int, string>  $done  the ids that have been
     */
    private function ratio(Items $content, array $done): ProgressRatio
    {
        $completed = $content
            ->filter(fn (PhaseContent $row): bool => in_array((string) $row->getKey(), $done, strict: true))
            ->count();

        return ProgressRatio::of($completed, $content->count());
    }

    /**
     * Narrow a set of required checklist items to those whose list sits in one phase.
     *
     * @param  Items<int, ChecklistItem>  $items
     * @param  array<string, string|null>  $itemPhases  item id => the phase its checklist sits in
     * @return Items<int, ChecklistItem>
     */
    private function itemsInPhase(Items $items, array $itemPhases, string $phaseId): Items
    {
        return $items->filter(
            fn (ChecklistItem $item): bool => ($itemPhases[(string) $item->getKey()] ?? null) === $phaseId,
        );
    }

    /**
     * Count how many required checklist items have been ticked.
     *
     * @param  Items<int, ChecklistItem>  $items
     * @param  array<int, string>  $done
     */
    private function itemRatio(Items $items, array $done): ProgressRatio
    {
        $completed = $items
            ->filter(fn (ChecklistItem $item): bool => in_array((string) $item->getKey(), $done, strict: true))
            ->count();

        return ProgressRatio::of($completed, $items->count());
    }

    /**
     * The progress of an adoption whose version could not be read.
     *
     * Only reachable if the relation was not loaded, which a foreign key makes impossible in practice.
     * Returning zeroes rather than raising keeps a loading mistake from becoming a 500 on a screen whose
     * job is to show a bar.
     */
    private function empty(GameFramework $adoption): FrameworkProgress
    {
        return FrameworkProgress::of(
            gameFrameworkId: (string) $adoption->getKey(),
            frameworkVersionId: (string) $adoption->framework_version_id,
            criteria: ProgressRatio::none(),
            practices: ProgressRatio::none(),
            checklistItems: ProgressRatio::none(),
            prompts: ProgressRatio::none(),
            phaseProgress: [],
        );
    }
}
