<?php

namespace Modules\DesignFramework\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Domain\Models\PhaseContent;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;
use RuntimeException;

/**
 * The shared authorization plumbing for design framework requests.
 *
 * Two rules hold for every subclass, and together they are the module's whole defence against
 * acting on somebody else's framework or somebody else's game:
 *
 * - the framework, the version, the phase, the content, the workspace and the game all come from
 *   resolved route bindings, never from the request body, so a caller does not get to name what
 *   their permissions are checked against;
 * - the answer is the policy's {@see Response}, not a boolean, so its choice between "you may
 *   not" and "there is no such thing" survives all the way to the status code.
 *
 * The bindings themselves are chained — see `DesignFrameworkServiceProvider` — so a version
 * belonging to another framework, a phase belonging to another version, or a criterion belonging
 * to a version this game did not adopt all fail to resolve before any of this runs.
 *
 * One identifier escapes that arrangement because it has no route segment of its own: the phase a
 * piece of content is filed under, which arrives in a request body. It is checked explicitly by
 * `PhaseBelongsToVersion`, which resolves it through the version that owns it.
 */
abstract class FrameworkRequest extends FormRequest
{
    /**
     * The framework this request is about.
     */
    protected function framework(): Framework
    {
        return $this->bound('framework', Framework::class);
    }

    /**
     * The edition this request is about.
     */
    protected function version(): FrameworkVersion
    {
        return $this->bound('version', FrameworkVersion::class);
    }

    /**
     * The phase this request is about.
     */
    protected function phase(): DesignPhaseDefinition
    {
        return $this->bound('phase', DesignPhaseDefinition::class);
    }

    /**
     * The checklist this request is about.
     */
    protected function checklist(): Checklist
    {
        return $this->bound('checklist', Checklist::class);
    }

    /**
     * The workspace this request is scoped to.
     */
    protected function workspace(): Workspace
    {
        return $this->bound('workspace', Workspace::class);
    }

    /**
     * The game this request is about.
     */
    protected function game(): Game
    {
        return $this->bound('game', Game::class);
    }

    /**
     * The piece of content named by the given route parameter.
     *
     * Typed loosely because the five content types share every rule a request cares about — the
     * subclasses that need a specific type ask for it by name.
     */
    protected function content(string $parameter): PhaseContent
    {
        return $this->bound($parameter, PhaseContent::class);
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
     * Run a framework ability against the bound framework.
     */
    protected function inspectFramework(string $ability): Response
    {
        return $this->inspect($ability, [$this->framework()]);
    }

    /**
     * Run a framework ability against the bound version.
     *
     * Almost always `updateVersion`, which is the single ability behind the whole builder: it
     * answers both "may this account edit frameworks?" and "is this version still a draft?".
     */
    protected function inspectVersion(string $ability): Response
    {
        return $this->inspect($ability, [$this->version()]);
    }

    /**
     * Run a framework ability against the version some content belongs to.
     *
     * Content has no policy of its own. Everything inside a version is governed by the version's
     * own editability, which is exactly the rule section 47 asks for — so the ability is checked
     * against the version rather than against the row.
     */
    protected function inspectOwningVersion(string $ability, FrameworkVersion $version): Response
    {
        return $this->inspect($ability, [$version]);
    }

    /**
     * Run a game framework ability against the bound game.
     */
    protected function inspectGame(string $ability): Response
    {
        return $this->inspect($ability, [GameFramework::class, $this->game()]);
    }

    /**
     * Run a game framework ability against a resolved adoption.
     */
    protected function inspectAdoption(string $ability, GameFramework $adoption): Response
    {
        return $this->inspect($ability, [$adoption]);
    }

    /**
     * Read a bound route parameter, insisting it was actually bound.
     *
     * A missing binding is a wiring mistake rather than a bad request, so it raises rather than
     * becoming a 403 — a request form used on a route that does not bind what it needs should fail
     * loudly in development instead of quietly refusing everybody in production.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $type
     * @return TModel
     */
    private function bound(string $parameter, string $type)
    {
        $value = $this->route($parameter);

        if (! $value instanceof $type) {
            throw new RuntimeException(static::class." was used on a route without a bound [{$parameter}].");
        }

        return $value;
    }
}
