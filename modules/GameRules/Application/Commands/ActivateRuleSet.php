<?php

namespace Modules\GameRules\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Enums\RuleSetStatus;
use Modules\GameRules\Domain\Events\RuleSetActivated;
use Modules\GameRules\Domain\Events\RuleSetArchived;
use Modules\GameRules\Domain\Exceptions\InvalidRuleSetTransition;
use Modules\GameRules\Domain\Exceptions\RuleSetHasErrors;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\ValidationError;
use Modules\GameRules\Infrastructure\Analysis\RuleSetValidator;
use Modules\Identity\Domain\Models\User;

/**
 * Put a rule system into play.
 *
 * "These are the rules now." A design state has exactly one active rule set, so
 * activating one has to displace whichever was active before — and the only legal
 * move out of active is archived, which means activating a new rule system
 * retires the old one rather than demoting it back to a draft.
 *
 * That is the right behaviour rather than an accident of the lifecycle. A rule
 * set that was in play while playtests ran is a historical record; a draft is a
 * work in progress. Turning the first back into the second would make the rules a
 * session was played under editable again.
 *
 * ## The one place a finding stops something
 *
 * The validator reports and never refuses — a half-written rule system is full of
 * warnings, and a tool that blocked on them would be a tool nobody could start
 * with. Activation is the exception, and only for *errors*: a rule that is its own
 * ancestor or a transition pointing into another rule set makes "these are the
 * rules" a claim that cannot be true. Warnings never block, because a game with no
 * victory condition may be exactly what the designer meant.
 *
 * ## Why this is a transaction with a lock
 *
 * The uniqueness is enforced by a partial index, so two simultaneous activations
 * cannot both succeed — one would fail with a constraint violation reaching the
 * designer as a 500. The row lock makes them queue instead, so the second one sees
 * the first's result and retires it properly.
 */
final class ActivateRuleSet
{
    /**
     * How many problems are named in the refusal.
     *
     * Enough to act on without turning an exception message into the findings
     * screen, which is where somebody should be reading the full list.
     */
    private const PROBLEMS_TO_NAME = 3;

    public function __construct(
        private readonly RuleWorkGuard $guard,
        private readonly RuleSetValidator $validator,
    ) {}

    public function handle(User $actor, RuleSet $ruleSet): RuleSet
    {
        $this->guard->ensureRuleSetAcceptsLifecycleChange($ruleSet);

        $this->ensureItHoldsTogether($ruleSet);

        $superseded = DB::transaction(function () use ($ruleSet): ?RuleSet {
            /** @var RuleSet $locked */
            $locked = RuleSet::query()->lockForUpdate()->findOrFail($ruleSet->getKey());

            if ($locked->status === RuleSetStatus::Active) {
                return null;
            }

            if (! $locked->status->canTransitionTo(RuleSetStatus::Active)) {
                throw InvalidRuleSetTransition::between($locked->status, RuleSetStatus::Active);
            }

            $current = RuleSet::query()
                ->where('game_version_id', $locked->game_version_id)
                ->where('status', RuleSetStatus::Active)
                ->whereKeyNot($locked->getKey())
                ->lockForUpdate()
                ->first();

            if ($current !== null) {
                $current->status = RuleSetStatus::Archived;
                $current->save();
            }

            $locked->status = RuleSetStatus::Active;
            $locked->save();

            $ruleSet->status = RuleSetStatus::Active;

            return $current;
        });

        if ($superseded !== null) {
            event(new RuleSetArchived(
                ruleSetId: $superseded->getKey(),
                gameVersionId: $superseded->game_version_id,
                archivedBy: $actor->getKey(),
            ));
        }

        event(new RuleSetActivated(
            ruleSetId: $ruleSet->getKey(),
            gameVersionId: $ruleSet->game_version_id,
            supersededRuleSetId: $superseded?->getKey(),
            activatedBy: $actor->getKey(),
        ));

        return $ruleSet;
    }

    /**
     * Refuse to put a self-contradictory rule system into play.
     *
     * @throws RuleSetHasErrors
     */
    private function ensureItHoldsTogether(RuleSet $ruleSet): void
    {
        $errors = array_values(array_filter(
            $this->validator->validate($ruleSet),
            fn (ValidationError $finding): bool => $finding->isError(),
        ));

        if ($errors === []) {
            return;
        }

        throw RuleSetHasErrors::withCount(
            count($errors),
            array_map(
                fn (ValidationError $finding): string => $finding->message,
                array_slice($errors, 0, self::PROBLEMS_TO_NAME),
            ),
        );
    }
}
