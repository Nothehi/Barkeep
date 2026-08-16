<?php

namespace Modules\Playtesting\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Workspace\Domain\Models\Workspace;
use RuntimeException;

/**
 * The shared authorization plumbing for playtesting requests.
 *
 * Two rules hold for every subclass, and they are the module's whole defence
 * against acting on somebody else's playtest:
 *
 * - the workspace, the game, the playtest and the session all come from
 *   resolved route bindings, never from the request body, so a caller does not
 *   get to name what their permissions are checked against;
 * - the answer is the policy's {@see Response}, not a boolean, so its choice
 *   between "you may not" and "there is no such playtest" survives all the way
 *   to the status code.
 *
 * The bindings themselves are chained — see `PlaytestingServiceProvider` — so
 * a playtest belonging to another game, or a session belonging to another
 * playtest, fails to resolve before any of this runs.
 *
 * Two identifiers escape that arrangement because they have no route segment
 * of their own: the version a playtest tests, and the participant a piece of
 * evidence is attributed to. Both are checked explicitly, by rule objects that
 * resolve them through the object that owns them.
 */
abstract class PlaytestRequest extends FormRequest
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
     * The playtest this request is about.
     */
    protected function playtest(): Playtest
    {
        $playtest = $this->route('playtest');

        if (! $playtest instanceof Playtest) {
            throw new RuntimeException(static::class.' was used on a route without a bound playtest.');
        }

        return $playtest;
    }

    /**
     * The playtest session this request is about.
     *
     * Not called `session()`, which is already taken: `Request::session()`
     * returns the HTTP session store, and shadowing it would break the
     * framework in ways that would only show up somewhere else entirely.
     */
    protected function playtestSession(): PlaytestSession
    {
        $session = $this->route('session');

        if (! $session instanceof PlaytestSession) {
            throw new RuntimeException(static::class.' was used on a route without a bound session.');
        }

        return $session;
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
        return $this->inspect($ability, [Playtest::class, $this->game()]);
    }

    /**
     * Run an ability against the bound playtest.
     */
    protected function inspectPlaytest(string $ability): Response
    {
        return $this->inspect($ability, [$this->playtest()]);
    }

    /**
     * Run an ability against the bound session.
     */
    protected function inspectSession(string $ability): Response
    {
        return $this->inspect($ability, [$this->playtestSession()]);
    }
}
