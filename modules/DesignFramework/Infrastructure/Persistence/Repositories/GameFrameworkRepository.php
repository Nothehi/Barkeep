<?php

namespace Modules\DesignFramework\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\DesignFramework\Domain\Enums\CriterionRating;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\ChecklistItemCompletion;
use Modules\DesignFramework\Domain\Models\CriterionEvaluation;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Domain\Models\PracticeCompletion;
use Modules\DesignFramework\Domain\Models\PromptResponse;
use Modules\GameDesign\Domain\Models\Game;

/**
 * Every read the module performs against the game side of its schema.
 *
 * Two jobs, and the second is a security boundary rather than a convenience.
 *
 * **Reading a game's records.** An adoption is found through its game, never by id,
 * so there is no lookup that could return another studio's progress. The game
 * itself was resolved through a workspace by GameDesign's own binding, so the whole
 * ownership chain — workspace, game, adoption, record — is walked before any handler
 * runs.
 *
 * **Resolving framework content through an adoption.** A criterion id, a practice
 * id, a checklist item id and a prompt id all arrive in URLs, and none of them says
 * which version it came from. The `...In` methods look each one up *through* the
 * version the game actually adopted, so an id from v2 is not found for a game on v1
 * — there is no code path on which a mismatched pair is compared, found wanting and
 * rejected. It simply never resolves.
 *
 * That distinction matters because framework content is not secret. A criterion
 * belongs to a globally published version, so knowing its id proves nothing; what
 * stops one studio writing into another's progress is that the adoption comes from
 * the URL's game and the content comes from the adoption.
 *
 * Nothing here authorizes. Every caller runs a policy on the result.
 */
final class GameFrameworkRepository
{
    /**
     * The framework a game follows, if it follows one.
     *
     * The version and its framework are eager loaded, because everything a caller
     * does next needs them — rendering the adoption, checking immutability,
     * resolving content — and the adoption is useless without knowing what it points
     * at.
     */
    public function forGame(Game $game): ?GameFramework
    {
        $adoption = GameFramework::query()
            ->where('game_id', $game->getKey())
            ->with(['version.framework', 'adopter'])
            ->first();

        return $adoption === null ? null : $adoption->setRelation('game', $game);
    }

    /**
     * Determine whether a game already follows a framework.
     *
     * Cheaper than {@see forGame()} and used by `AssignFrameworkToGame`, which only
     * needs to know whether to refuse.
     */
    public function gameHasFramework(Game $game): bool
    {
        return GameFramework::query()->where('game_id', $game->getKey())->exists();
    }

    /**
     * Find one of the adopted version's criteria.
     *
     * Scoped by the version rather than compared against it, which is the difference between "the
     * mismatch is caught" and "the mismatch cannot happen".
     *
     * @return DesignCriterion|null null when the id names nothing in this version
     */
    public function findCriterionIn(GameFramework $adoption, string $criterionId): ?DesignCriterion
    {
        $version = $adoption->version;

        return $version === null ? null : DesignCriterion::query()
            ->where('framework_version_id', $version->getKey())
            ->whereKey($criterionId)
            ->first();
    }

    /**
     * Find one of the adopted version's practices.
     */
    public function findPracticeIn(GameFramework $adoption, string $practiceId): ?DesignPractice
    {
        $version = $adoption->version;

        return $version === null ? null : DesignPractice::query()
            ->where('framework_version_id', $version->getKey())
            ->whereKey($practiceId)
            ->first();
    }

    /**
     * Find one of the adopted version's prompts.
     */
    public function findPromptIn(GameFramework $adoption, string $promptId): ?DesignPrompt
    {
        $version = $adoption->version;

        return $version === null ? null : DesignPrompt::query()
            ->where('framework_version_id', $version->getKey())
            ->whereKey($promptId)
            ->first();
    }

    /**
     * Find one checklist item belonging to the adopted version.
     *
     * Reached through the checklists rather than directly, because an item has no
     * version column of its own — its version is its checklist's. That is one join
     * more than the other three and the same guarantee.
     */
    public function findChecklistItemIn(GameFramework $adoption, string $itemId): ?ChecklistItem
    {
        $version = $adoption->version;

        if ($version === null) {
            return null;
        }

        return ChecklistItem::query()
            ->whereKey($itemId)
            ->whereHas(
                'checklist',
                fn ($query) => $query->where('framework_version_id', $version->getKey()),
            )
            ->with('checklist')
            ->first();
    }

    /**
     * A game's standing answer to one criterion, if it has one.
     */
    public function findEvaluation(GameFramework $adoption, DesignCriterion $criterion): ?CriterionEvaluation
    {
        return $adoption->criterionEvaluations()
            ->where('criterion_id', $criterion->getKey())
            ->first();
    }

    /**
     * A game's completion of one practice, if it has one.
     */
    public function findPracticeCompletion(GameFramework $adoption, DesignPractice $practice): ?PracticeCompletion
    {
        return $adoption->practiceCompletions()
            ->where('practice_id', $practice->getKey())
            ->first();
    }

    /**
     * A game's tick against one checklist item, if it has one.
     */
    public function findItemCompletion(GameFramework $adoption, ChecklistItem $item): ?ChecklistItemCompletion
    {
        return $adoption->checklistItemCompletions()
            ->where('checklist_item_id', $item->getKey())
            ->first();
    }

    /**
     * A game's answer to one prompt, if it has one.
     */
    public function findResponse(GameFramework $adoption, DesignPrompt $prompt): ?PromptResponse
    {
        return $adoption->promptResponses()
            ->where('prompt_id', $prompt->getKey())
            ->first();
    }

    /**
     * Every assessment this game has made, newest first.
     *
     * @return Collection<int, CriterionEvaluation>
     */
    public function evaluationsOf(GameFramework $adoption): Collection
    {
        return $adoption->criterionEvaluations()
            ->with(['criterion', 'evaluator'])
            ->orderByDesc('evaluated_at')
            ->get();
    }

    /**
     * Every practice this game has carried out, newest first.
     *
     * @return Collection<int, PracticeCompletion>
     */
    public function practiceCompletionsOf(GameFramework $adoption): Collection
    {
        return $adoption->practiceCompletions()
            ->with(['practice', 'completer'])
            ->orderByDesc('completed_at')
            ->get();
    }

    /**
     * Every checklist requirement this game has met, newest first.
     *
     * @return Collection<int, ChecklistItemCompletion>
     */
    public function itemCompletionsOf(GameFramework $adoption): Collection
    {
        return $adoption->checklistItemCompletions()
            ->with(['item', 'completer'])
            ->orderByDesc('completed_at')
            ->get();
    }

    /**
     * Every question this game has answered, newest first.
     *
     * @return Collection<int, PromptResponse>
     */
    public function responsesOf(GameFramework $adoption): Collection
    {
        return $adoption->promptResponses()
            ->with(['prompt', 'author'])
            ->orderByDesc('answered_at')
            ->get();
    }

    /**
     * The ids of the criteria this game has assessed, mapped to their grades.
     *
     * A map rather than models, because this feeds the progress calculation, which
     * needs to know *which* criteria have been answered across a whole version and
     * has no use for the notes.
     *
     * @return array<string, string> criterion id => rating value
     */
    public function evaluatedCriterionIds(GameFramework $adoption): array
    {
        /** @var array<string, string> $map */
        $map = $adoption->criterionEvaluations()
            ->pluck('status', 'criterion_id')
            ->map(fn (mixed $status): string => $status instanceof CriterionRating
                ? $status->value
                : (string) $status)
            ->all();

        return $map;
    }

    /**
     * The ids of the practices this game has completed.
     *
     * @return list<string>
     */
    public function completedPracticeIds(GameFramework $adoption): array
    {
        /** @var list<string> $ids */
        $ids = $adoption->practiceCompletions()->pluck('practice_id')->all();

        return $ids;
    }

    /**
     * The ids of the checklist items this game has ticked.
     *
     * @return list<string>
     */
    public function completedItemIds(GameFramework $adoption): array
    {
        /** @var list<string> $ids */
        $ids = $adoption->checklistItemCompletions()->pluck('checklist_item_id')->all();

        return $ids;
    }

    /**
     * The ids of the prompts this game has answered.
     *
     * @return list<string>
     */
    public function answeredPromptIds(GameFramework $adoption): array
    {
        /** @var list<string> $ids */
        $ids = $adoption->promptResponses()->pluck('prompt_id')->all();

        return $ids;
    }

    /**
     * The checklists of the adopted version, with their items.
     *
     * @return Collection<int, Checklist>
     */
    public function checklistsOf(GameFramework $adoption): Collection
    {
        $version = $adoption->version;

        if ($version === null) {
            return Checklist::query()->whereRaw('1 = 0')->get();
        }

        return Checklist::query()
            ->where('framework_version_id', $version->getKey())
            ->visible()
            ->with('items')
            ->ordered()
            ->get();
    }
}
