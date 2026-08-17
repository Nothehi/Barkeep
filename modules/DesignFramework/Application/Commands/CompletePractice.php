<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\CompletionData;
use Modules\DesignFramework\Application\Services\FrameworkContentLocator;
use Modules\DesignFramework\Application\Services\GameFrameworkGuard;
use Modules\DesignFramework\Domain\Events\PracticeCompleted;
use Modules\DesignFramework\Domain\Models\DesignPractice;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Domain\Models\PracticeCompletion;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\GameFrameworkRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Record that a game has carried out one of its framework's activities.
 *
 * The practice belongs to the framework; the completion belongs to the game's adoption of
 * it. Section 23 calls that separation critical, and the reason is concrete: "run a
 * two-player playtest" is permanent advice to everybody following the version and a
 * finished task for exactly one project.
 *
 * ## Why this is a toggle
 *
 * The command is named for the common case and handles both directions, because a
 * checkbox has two of them. Unticking *deletes* the completion rather than storing a
 * false flag — the row's existence is the fact, so a `completed = false` row would be a
 * completion record that records no completion, and every count in the module would then
 * have to remember to filter it out.
 *
 * The event fires only on completion. A consumer awarding something for work done should
 * not have to reason about whether the work was later disclaimed, and a
 * "practice un-completed" event would exist mainly to be mishandled.
 *
 * Re-completing an already complete practice updates its notes and timestamp rather than
 * failing, which is what makes the endpoint safe to retry.
 */
final class CompletePractice
{
    public function __construct(
        private readonly GameFrameworkGuard $guard,
        private readonly FrameworkContentLocator $content,
        private readonly GameFrameworkRepository $adoptions,
    ) {}

    public function handle(
        User $actor,
        GameFramework $adoption,
        DesignPractice $practice,
        CompletionData $data,
    ): ?PracticeCompletion {
        $this->guard->ensureAdoptionAcceptsProgress($adoption);
        $this->content->ensureAdopted($adoption, $practice);

        $existing = $this->adoptions->findPracticeCompletion($adoption, $practice);

        if (! $data->completed) {
            $existing?->delete();

            return null;
        }

        $completedAt = now()->toImmutable();
        $wasComplete = $existing !== null;

        $completion = $existing ?? new PracticeCompletion;

        $completion->fill(['notes' => $data->notes]);

        $completion->game_framework_id = $adoption->getKey();
        $completion->practice_id = $practice->getKey();
        $completion->completed_by = $actor->id;
        $completion->completed_at = $completedAt;

        $completion->save();

        $completion->setRelation('gameFramework', $adoption);
        $completion->setRelation('practice', $practice);
        $completion->setRelation('completer', $actor);

        if (! $wasComplete) {
            event(new PracticeCompleted(
                completionId: $completion->id,
                gameFrameworkId: $adoption->getKey(),
                gameId: $adoption->game_id,
                practiceId: $practice->getKey(),
                completedBy: $actor->id,
                completedAt: $completedAt->toDateTimeImmutable(),
            ));
        }

        return $completion;
    }
}
