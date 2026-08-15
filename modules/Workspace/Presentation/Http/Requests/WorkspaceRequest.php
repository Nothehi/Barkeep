<?php

namespace Modules\Workspace\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;
use RuntimeException;

/**
 * The shared authorization plumbing for workspace-scoped requests.
 *
 * Every subclass answers "may you do this?" by asking the policy, and returns
 * the policy's {@see Response} rather than a boolean, so its choice between
 * "you may not" and "there is no such workspace" survives all the way to the
 * status code.
 */
abstract class WorkspaceRequest extends FormRequest
{
    /**
     * The workspace this request is scoped to.
     *
     * Read from the resolved route binding, never from the request body: a
     * caller does not get to name the workspace their permissions are checked
     * against.
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
     * The signed in account.
     */
    protected function actor(): ?User
    {
        $user = $this->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Run a workspace ability against the policy.
     *
     * @param  array<int, mixed>  $arguments  extra policy arguments, such as the member being acted on
     */
    protected function inspect(string $ability, array $arguments = []): Response
    {
        $user = $this->actor();

        if ($user === null) {
            return Response::deny();
        }

        return Gate::forUser($user)->inspect($ability, [$this->workspace(), ...$arguments]);
    }
}
