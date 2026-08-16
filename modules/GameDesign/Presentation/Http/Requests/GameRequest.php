<?php

namespace Modules\GameDesign\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;
use RuntimeException;

/**
 * The shared authorization plumbing for game-scoped requests.
 *
 * Two rules hold for every subclass, and they are the module's whole defence
 * against acting on somebody else's game:
 *
 * - the workspace and the game come from resolved route bindings, never from
 *   the request body, so a caller does not get to name what their permissions
 *   are checked against;
 * - the answer is the policy's {@see Response}, not a boolean, so its choice
 *   between "you may not" and "there is no such game" survives all the way to
 *   the status code.
 *
 * The bindings themselves are scoped — see `routes/games.php` — so a game
 * address that belongs to another workspace fails to resolve before any of
 * this runs.
 */
abstract class GameRequest extends FormRequest
{
    /**
     * The workspace this request is scoped to.
     */
    protected function workspace(): Workspace
    {
        $workspace = $this->route('workspace');

        if (! $workspace instanceof Workspace) {
            throw new RuntimeException(static::class.' was used on a route without a bound workspace.');
        }

        return $workspace;
    }

    /**
     * The game this request is about.
     */
    protected function game(): Game
    {
        $game = $this->route('game');

        if (! $game instanceof Game) {
            throw new RuntimeException(static::class.' was used on a route without a bound game.');
        }

        return $game;
    }

    /**
     * The signed in account.
     */
    protected function actor(): ?User
    {
        $user = $this->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Run an ability against the policy, with whatever it is about.
     *
     * @param  array<int, mixed>  $arguments
     */
    protected function inspect(string $ability, array $arguments): Response
    {
        $user = $this->actor();

        if ($user === null) {
            return Response::deny();
        }

        return Gate::forUser($user)->inspect($ability, $arguments);
    }

    /**
     * Run an ability against the bound game.
     */
    protected function inspectGame(string $ability): Response
    {
        return $this->inspect($ability, [$this->game()]);
    }
}
