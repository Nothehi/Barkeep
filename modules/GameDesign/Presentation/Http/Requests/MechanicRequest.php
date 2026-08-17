<?php

namespace Modules\GameDesign\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Application\DTOs\MechanicData;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\Identity\Domain\Models\User;
use RuntimeException;

/**
 * The shared plumbing for vocabulary requests.
 *
 * Notice what is absent compared with {@see GameRequest}: there is no workspace
 * and no game. The vocabulary is the platform's, so these requests are scoped
 * by nothing, and the only question the policy asks is whether the caller
 * curates it. That is the whole difference between the two halves of this
 * module, and keeping the base classes separate is what stops a mechanic
 * request from growing a workspace it should not have.
 */
abstract class MechanicRequest extends FormRequest
{
    /**
     * The mechanic this request is about, when there is one.
     */
    protected function mechanic(): Mechanic
    {
        $mechanic = $this->route('mechanic');

        if (! $mechanic instanceof Mechanic) {
            throw new RuntimeException(static::class.' was used on a route without a bound mechanic.');
        }

        return $mechanic;
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
     * Run an ability against the policy.
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
     * Get the validated input as an application layer DTO.
     */
    public function toData(): MechanicData
    {
        return MechanicData::fromArray($this->validated());
    }
}
