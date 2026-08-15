<?php

namespace Modules\GameDesign\Application\Validation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\ValueObjects\GameSlug;

trait GameValidationRules
{
    /**
     * Get the validation rules used to validate a game's own metadata.
     *
     * @param  string  $workspaceId  the workspace the address has to be free in
     * @param  string|null  $gameId  the game allowed to keep its own address
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function gameRules(string $workspaceId, ?string $gameId = null, bool $slugIsRequired = true): array
    {
        return [
            'name' => $this->gameNameRules(),
            'slug' => $this->gameSlugRules($workspaceId, $gameId, $slugIsRequired),
            'description' => $this->gameDescriptionRules(),
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function gameNameRules(): array
    {
        return ['required', 'string', 'min:2', 'max:120'];
    }

    /**
     * Get the validation rules used to validate a game address.
     *
     * Uniqueness is checked within the workspace rather than globally, which
     * is the whole point of the constraint: another studio's game at the same
     * address is not a collision. The workspace id comes from the resolved
     * route binding, so a caller cannot widen or move the scope it is checked
     * against.
     *
     * The format check delegates to the value object rather than restating
     * the pattern, so the boundary and the domain stay in step.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function gameSlugRules(string $workspaceId, ?string $gameId = null, bool $required = true): array
    {
        $unique = Rule::unique(Game::class, 'slug')
            ->where(fn ($query) => $query->where('workspace_id', $workspaceId));

        return [
            $required ? 'required' : 'nullable',
            'string',
            'min:'.GameSlug::MIN_LENGTH,
            'max:'.GameSlug::MAX_LENGTH,
            new ValidGameSlug,
            $gameId === null ? $unique : $unique->ignore($gameId),
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function gameDescriptionRules(): array
    {
        return ['nullable', 'string', 'max:2000'];
    }

    /**
     * Get the validation rules used to validate a design phase.
     *
     * Every phase is acceptable, in both directions: a game that drops back
     * from playtesting to prototyping is doing the normal thing.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function designPhaseRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            Rule::enum(DesignPhase::class),
        ];
    }

    /**
     * Get the validation rules used to validate a requested status.
     *
     * The enum check only establishes that the value names a real status.
     * Whether *this* game may move to it is a domain question, answered by
     * the transition matrix once the game has been resolved — validation
     * cannot answer it, because it does not know where the game is coming
     * from.
     *
     * Archived is excluded here because archival has its own endpoint, and a
     * status change that happened to be irreversible would be an unpleasant
     * thing to reach by accident.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function gameStatusRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            Rule::enum(GameStatus::class)->except(GameStatus::Archived),
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function versionNameRules(): array
    {
        return ['nullable', 'string', 'max:120'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function versionDescriptionRules(): array
    {
        return ['nullable', 'string', 'max:5000'];
    }

    /**
     * Get the validation rules used to validate the games list filters.
     *
     * Every filter is optional, and a value that names nothing is treated as
     * no filter rather than as an error — see `GameFilters`. The rules here
     * only keep the query string from carrying something absurd.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function gameFilterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::enum(GameStatus::class)],
            'design_phase' => ['nullable', 'string', Rule::enum(DesignPhase::class)],
        ];
    }
}
