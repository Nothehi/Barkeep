<?php

namespace Modules\GameDesign\Application\Validation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Modules\GameDesign\Domain\Enums\MechanicCategory;

/**
 * The rules a term in the vocabulary is held to.
 *
 * Separate from {@see GameValidationRules} rather than folded into it, because
 * nothing here is game-scoped: a mechanic has no workspace to be unique inside
 * and no game to be checked against. Sharing the trait would put a
 * `$workspaceId` parameter in reach of rules that must never take one.
 *
 * There is no rule for the address. A mechanic's slug is derived from its name
 * by `MechanicSlugAllocator` and never submitted, so validating one would be
 * validating a field the form does not have.
 */
trait MechanicValidationRules
{
    /**
     * Get the validation rules used to validate a mechanic.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function mechanicRules(): array
    {
        return [
            'name' => $this->mechanicNameRules(),
            'description' => $this->mechanicDescriptionRules(),
            'category' => $this->mechanicCategoryRules(),
        ];
    }

    /**
     * Short, because these are terms rather than sentences. A "mechanic" whose
     * name runs past a few words is a description that has been put in the
     * wrong field.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function mechanicNameRules(): array
    {
        return ['required', 'string', 'min:2', 'max:80'];
    }

    /**
     * Generous, because this is where the term is actually defined and a
     * definition that has to be terse is a definition people will argue about.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function mechanicDescriptionRules(): array
    {
        return ['nullable', 'string', 'max:2000'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function mechanicCategoryRules(): array
    {
        return ['required', Rule::enum(MechanicCategory::class)];
    }

    /**
     * Get the validation rules used when a game claims a set of mechanics.
     *
     * Shape only. Whether the ids name terms that exist, and terms that are
     * still offered, is `UpdateDesignRecord`'s decision — it has to load them
     * anyway, and a validation rule that queried for each id would be the same
     * work done twice and answered less precisely.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function mechanicSelectionRules(): array
    {
        return [
            'mechanics' => ['sometimes', 'array', 'max:30'],
            'mechanics.*' => ['string', 'uuid'],
        ];
    }
}
