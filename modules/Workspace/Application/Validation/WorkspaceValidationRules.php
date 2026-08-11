<?php

namespace Modules\Workspace\Application\Validation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\ValueObjects\WorkspaceSlug;

trait WorkspaceValidationRules
{
    /**
     * Get the validation rules used to validate a workspace's own settings.
     *
     * @param  string|null  $workspaceId  the workspace allowed to keep its own address
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function workspaceRules(?string $workspaceId = null, bool $slugIsRequired = true): array
    {
        return [
            'name' => $this->workspaceNameRules(),
            'slug' => $this->workspaceSlugRules($workspaceId, $slugIsRequired),
            'description' => $this->workspaceDescriptionRules(),
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function workspaceNameRules(): array
    {
        return ['required', 'string', 'min:2', 'max:120'];
    }

    /**
     * Get the validation rules used to validate a workspace address.
     *
     * The format check delegates to the value object rather than restating
     * the pattern, so the boundary and the domain can never disagree about
     * what a valid address looks like.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function workspaceSlugRules(?string $workspaceId = null, bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'min:'.WorkspaceSlug::MIN_LENGTH,
            'max:'.WorkspaceSlug::MAX_LENGTH,
            new ValidWorkspaceSlug,
            $workspaceId === null
                ? Rule::unique(Workspace::class, 'slug')
                : Rule::unique(Workspace::class, 'slug')->ignore($workspaceId),
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function workspaceDescriptionRules(): array
    {
        return ['nullable', 'string', 'max:2000'];
    }

    /**
     * Get the validation rules used to validate a role a caller supplied.
     *
     * Only the assignable roles are accepted. Ownership never arrives over
     * the wire — it moves through an explicit transfer.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function assignableRoleRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            Rule::enum(WorkspaceRole::class)->only(WorkspaceRole::assignable()),
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function invitationEmailRules(): array
    {
        return ['required', 'string', 'email', 'max:255'];
    }
}
