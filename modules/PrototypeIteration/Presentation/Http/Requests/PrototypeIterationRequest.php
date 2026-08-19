<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\IterationPlaytest;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeArtifact;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\Workspace\Domain\Models\Workspace;
use RuntimeException;

/**
 * The shared authorization plumbing for prototype and iteration requests.
 *
 * Two rules hold for every subclass, and together they are the module's whole defence against
 * acting on somebody else's design work:
 *
 * - the workspace, the game, the prototype, the version, the iteration and every child record
 *   come from resolved route bindings, never from the request body, so a caller does not get to
 *   name what their permissions are checked against;
 * - the answer is the policy's {@see Response}, not a boolean, so its choice between "you may
 *   not" and "there is no such thing" survives all the way to the status code.
 *
 * The bindings themselves are chained — see `PrototypeIterationServiceProvider` — so a prototype
 * belonging to another game, or a change belonging to another cycle, fails to resolve before any
 * of this runs.
 *
 * Four identifiers escape that arrangement because they have no route segment of their own: the
 * design version a prototype or iteration is based on, the prototype version an iteration is
 * about, the playtest an iteration is attached to, and the record a decision cites. All four are
 * checked explicitly, by rule objects that resolve them through the game that owns them — which
 * is why those rules exist at all, and why none of them is an `exists` clause.
 */
abstract class PrototypeIterationRequest extends FormRequest
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
     * The prototype this request is about.
     */
    protected function prototype(): Prototype
    {
        $prototype = $this->route('prototype');

        if (! $prototype instanceof Prototype) {
            throw new RuntimeException(static::class.' was used on a route without a bound prototype.');
        }

        return $prototype;
    }

    /**
     * The prototype state this request is about.
     *
     * Not called `version()`, which would be ambiguous in a module where a game version and a
     * prototype version both appear on the same request — the route parameter is
     * `{prototypeVersion}` for the same reason.
     */
    protected function prototypeVersion(): PrototypeVersion
    {
        $version = $this->route('prototypeVersion');

        if (! $version instanceof PrototypeVersion) {
            throw new RuntimeException(static::class.' was used on a route without a bound prototype version.');
        }

        return $version;
    }

    /**
     * The artifact this request is about.
     */
    protected function artifact(): PrototypeArtifact
    {
        $artifact = $this->route('artifact');

        if (! $artifact instanceof PrototypeArtifact) {
            throw new RuntimeException(static::class.' was used on a route without a bound artifact.');
        }

        return $artifact;
    }

    /**
     * The design cycle this request is about.
     */
    protected function iteration(): Iteration
    {
        $iteration = $this->route('iteration');

        if (! $iteration instanceof Iteration) {
            throw new RuntimeException(static::class.' was used on a route without a bound iteration.');
        }

        return $iteration;
    }

    /**
     * The design change this request is about.
     */
    protected function change(): DesignChange
    {
        $change = $this->route('change');

        if (! $change instanceof DesignChange) {
            throw new RuntimeException(static::class.' was used on a route without a bound change.');
        }

        return $change;
    }

    /**
     * The experiment this request is about.
     */
    protected function experiment(): DesignExperiment
    {
        $experiment = $this->route('experiment');

        if (! $experiment instanceof DesignExperiment) {
            throw new RuntimeException(static::class.' was used on a route without a bound experiment.');
        }

        return $experiment;
    }

    /**
     * The decision this request is about.
     */
    protected function decision(): DesignDecision
    {
        $decision = $this->route('decision');

        if (! $decision instanceof DesignDecision) {
            throw new RuntimeException(static::class.' was used on a route without a bound decision.');
        }

        return $decision;
    }

    /**
     * The playtest association this request is about.
     *
     * A link rather than a playtest, deliberately. Detaching addresses the association, so the
     * route names nothing belonging to Playtesting — which is what keeps this module's HTTP
     * surface free of another context's records.
     */
    protected function playtestLink(): IterationPlaytest
    {
        $link = $this->route('link');

        if (! $link instanceof IterationPlaytest) {
            throw new RuntimeException(static::class.' was used on a route without a bound playtest link.');
        }

        return $link;
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
     * Run a prototype ability against the bound game.
     */
    protected function inspectGameForPrototypes(string $ability): Response
    {
        return $this->inspect($ability, [Prototype::class, $this->game()]);
    }

    /**
     * Run an iteration ability against the bound game.
     */
    protected function inspectGameForIterations(string $ability): Response
    {
        return $this->inspect($ability, [Iteration::class, $this->game()]);
    }

    /**
     * Run an ability against the bound prototype.
     */
    protected function inspectPrototype(string $ability): Response
    {
        return $this->inspect($ability, [$this->prototype()]);
    }

    /**
     * Run an ability against the bound iteration.
     */
    protected function inspectIteration(string $ability): Response
    {
        return $this->inspect($ability, [$this->iteration()]);
    }
}
