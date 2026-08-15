<?php

namespace Modules\GameDesign\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\GameDesign\Application\Services\GameModificationGuard;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Events\GameArchived;
use Modules\GameDesign\Domain\Events\GameStatusChanged;
use Modules\GameDesign\Domain\Exceptions\InvalidStatusTransition;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;

/**
 * Move a game through its project lifecycle.
 *
 * Every status change in the module goes through here, including archival —
 * which is why the transition matrix on {@see GameStatus} is the whole truth
 * about what is possible rather than one of several opinions.
 *
 * The move is decided under a row lock and against the status read inside it,
 * not against whatever the caller was looking at. Two people pressing
 * "Complete" and "Put on hold" at the same moment therefore produce one
 * winner and one honest refusal, instead of a last-write-wins result where
 * the losing action reports success.
 */
final class ChangeGameStatus
{
    public function __construct(private readonly GameModificationGuard $guard) {}

    public function handle(User $actor, Game $game, GameStatus $target): Game
    {
        $this->guard->ensureGameIsModifiable($game);

        $from = DB::transaction(function () use ($game, $target): GameStatus {
            $fresh = Game::query()
                ->whereKey($game->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $from = $fresh->status;

            if ($from === $target) {
                return $from;
            }

            if (! $from->canTransitionTo($target)) {
                throw InvalidStatusTransition::between($from, $target);
            }

            $fresh->forceFill(['status' => $target])->save();

            /**
             * Carry the saved row back onto the instance the caller holds, so
             * what gets rendered afterwards is the state that was written
             * rather than the one that was read before the lock.
             */
            $game->setRawAttributes($fresh->getAttributes(), sync: true);

            return $from;
        });

        if ($from === $target) {
            return $game;
        }

        event(new GameStatusChanged(
            gameId: $game->id,
            workspaceId: $game->workspace_id,
            changedBy: $actor->id,
            from: $from,
            to: $target,
        ));

        /**
         * Archival gets an event of its own as well as the generic one.
         * "This game was put away" is a different fact from "this game
         * changed status", and consumers that only care about the first —
         * stopping scheduled work, closing open playtests — should not have
         * to inspect a status to find it.
         */
        if ($target === GameStatus::Archived) {
            event(new GameArchived(
                gameId: $game->id,
                workspaceId: $game->workspace_id,
                archivedBy: $actor->id,
                archivedAt: now(),
            ));
        }

        return $game;
    }
}
