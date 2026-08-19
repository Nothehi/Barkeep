<?php

namespace Modules\GameEconomy\Application\Validation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Modules\GameEconomy\Domain\Enums\ActionEffectType;
use Modules\GameEconomy\Domain\Enums\AssumptionCategory;
use Modules\GameEconomy\Domain\Enums\AssumptionConfidence;
use Modules\GameEconomy\Domain\Enums\BalanceProfileStatus;
use Modules\GameEconomy\Domain\Enums\BalanceScenarioStatus;
use Modules\GameEconomy\Domain\Enums\BalanceVariableCategory;
use Modules\GameEconomy\Domain\Enums\ObservationSeverity;
use Modules\GameEconomy\Domain\Enums\ObservationSourceType;
use Modules\GameEconomy\Domain\Enums\ResourceCategory;
use Modules\GameEconomy\Domain\Enums\ResourceFlowType;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceScenario;

/**
 * The validation rules the module's form requests share.
 *
 * Gathered in one trait so that a limit has a single definition. The lengths
 * here are the same numbers the TypeScript schema uses to give somebody
 * immediate feedback as they type — the server's answer always wins, but the two
 * agreeing is what stops a form from accepting something the request will then
 * refuse.
 *
 * Nothing here decides ownership. Whether a resource, an action or a variable
 * belongs to this configuration are questions with their own rule classes beside
 * this file, each of which resolves through the same catalogue the commands use.
 *
 * ## Why the numeric rules look the way they do
 *
 * Every amount is `numeric` with a bound, and never `integer` — a game that pays
 * out half a coin is unusual but real, and refusing it would be the platform
 * deciding what a designer's economy may contain.
 *
 * The ceilings exist because the columns are `decimal(20, 6)`: a value with more
 * than fourteen digits before the point cannot be stored, and a request that
 * accepted one would fail at the database with a message nobody can act on.
 * `decimal:0,6` refuses more precision than the column keeps, for the same
 * reason — silently truncating a designer's number is worse than telling them.
 *
 * ## Why the floors are where they are
 *
 * Most minimums are deliberately low, because most of this is typed while
 * somebody is mid-thought. Two fields have real floors, and each is a field
 * somebody will read in a year and have to understand without asking anybody: an
 * assumption's title, because "food matters" is not a belief anybody can test,
 * and an observation's body, because a severity with no account of what was seen
 * is an alarm without a reason.
 */
trait EconomyValidationRules
{
    /**
     * The largest magnitude a `decimal(20, 6)` column can hold.
     *
     * Fourteen digits before the point, which is far beyond any real board game
     * and exactly at the edge of what the column accepts.
     */
    private const MAX_AMOUNT = '99999999999999';

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function profileNameRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:2', 'max:160'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function profileDescriptionRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:5000'];
    }

    /**
     * Get the rules for filtering a profiles list by status.
     *
     * Only used on the list filter. A profile's status is never set by a PATCH —
     * activating and archiving are actions with their own endpoints, which keeps
     * an irreversible move from being one field value away from a reversible
     * one.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function profileStatusRules(): array
    {
        return ['nullable', 'string', Rule::enum(BalanceProfileStatus::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function resourceNameRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:1', 'max:120'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function resourceCategoryRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(ResourceCategory::class)];
    }

    /**
     * Get the rules for what one of something is called: "cubes", "coins".
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function unitRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:40'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function descriptionRules(int $max = 5000): array
    {
        return ['sometimes', 'nullable', 'string', 'max:'.$max];
    }

    /**
     * Get the rules for a flag a designer sets on a resource.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function flagRules(): array
    {
        return ['sometimes', 'boolean'];
    }

    /**
     * Get the rules for an amount that may be absent.
     *
     * Nullable throughout, because null means unbounded and that is a statement
     * a designer makes on purpose — a resource with no ceiling is a shape the
     * analysis reports, not a field somebody forgot.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function optionalAmountRules(): array
    {
        return [
            'sometimes',
            'nullable',
            'numeric',
            'decimal:0,6',
            'min:-'.self::MAX_AMOUNT,
            'max:'.self::MAX_AMOUNT,
        ];
    }

    /**
     * Get the rules for an amount that has to be there.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function requiredAmountRules(): array
    {
        return [
            'required',
            'numeric',
            'decimal:0,6',
            'min:-'.self::MAX_AMOUNT,
            'max:'.self::MAX_AMOUNT,
        ];
    }

    /**
     * Get the rules for a magnitude, which is never negative.
     *
     * Costs, rewards and flow amounts all use this. Direction belongs to the
     * flow type and to which table the row is in, so a negative here would be a
     * second, contradictory way of saying it.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function magnitudeRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'sometimes',
            'numeric',
            'decimal:0,6',
            'min:0',
            'max:'.self::MAX_AMOUNT,
        ];
    }

    /**
     * Get the rules for the designer's own ordering of a list.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function positionRules(): array
    {
        return ['sometimes', 'integer', 'min:0', 'max:100000'];
    }

    /**
     * Get the rules for the resource a flow, cost or reward names.
     *
     * The ownership check is a rule object rather than an `exists` with a
     * `where`, so the "which resources belong to this profile" question has one
     * definition instead of a second copy written in a validator.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function resourceReferenceRules(BalanceProfile $profile, bool $required = true): array
    {
        return [
            $required ? 'required' : 'sometimes',
            'string',
            new ResourceBelongsToProfile($profile),
        ];
    }

    /**
     * Get the rules for the resource a variable is optionally about.
     *
     * Nullable, because most variables are about the game rather than about
     * anything in the model — and clearing the reference has to be possible.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function optionalResourceReferenceRules(BalanceProfile $profile): array
    {
        return ['sometimes', 'nullable', 'string', new ResourceBelongsToProfile($profile)];
    }

    /**
     * Get the rules for the action a variable is optionally about.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function optionalActionReferenceRules(BalanceProfile $profile): array
    {
        return ['sometimes', 'nullable', 'string', new ActionBelongsToProfile($profile)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function flowNameRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:2', 'max:160'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function flowTypeRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(ResourceFlowType::class)];
    }

    /**
     * Get the rules for when a flow happens.
     *
     * Prose, and short. This module models an economy rather than executing one,
     * so the condition is written for a person to read — an expression language
     * here would be a simulator wearing a text column.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function conditionRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:500'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function actionNameRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:1', 'max:120'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function effectTypeRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(ActionEffectType::class)];
    }

    /**
     * Get the rules for what an effect acts on.
     *
     * Free text and required. The things an effect targets are not all rows —
     * "building level 2" is not a resource — so there is nothing to validate it
     * against beyond it being said at all.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function effectTargetRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:1', 'max:160'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function variableNameRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:1', 'max:120'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function variableCategoryRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(BalanceVariableCategory::class)];
    }

    /**
     * Get the rules for the increment a designer tunes a variable in.
     *
     * Positive, because a step of zero or less is not an increment.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function stepRules(): array
    {
        return [
            'sometimes',
            'nullable',
            'numeric',
            'decimal:0,6',
            'gt:0',
            'max:'.self::MAX_AMOUNT,
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function scenarioNameRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:2', 'max:120'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function scenarioStatusRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(BalanceScenarioStatus::class)];
    }

    /**
     * Get the rules for the variable a scenario overrides.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function scenarioVariableRules(BalanceScenario $scenario): array
    {
        return ['required', 'string', new VariableBelongsToScenario($scenario)];
    }

    /**
     * Get the rules for the belief an assumption records.
     *
     * Floored at ten characters, because "food matters" is not a belief anybody
     * can test — and an assumption nobody can test is a note, which is what the
     * description is for.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function assumptionTitleRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:10', 'max:200'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function assumptionCategoryRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(AssumptionCategory::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function confidenceRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(AssumptionConfidence::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function observationTitleRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:3', 'max:200'];
    }

    /**
     * Get the rules for what the studio actually saw.
     *
     * Floored, because a severity with no account of what was observed is an
     * alarm without a reason — and somebody reading it in six months has no way
     * to decide whether it still applies.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function observationBodyRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:10', 'max:5000'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function observationSourceRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(ObservationSourceType::class)];
    }

    /**
     * Get the rules for where an observation's evidence came from.
     *
     * A plain string, deliberately unvalidated against anything. Resolving a
     * playtest id here would mean this module importing Playtesting, and once it
     * can do that it ends up holding a copy of the evidence rather than a
     * pointer to it.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function sourceReferenceRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:200'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function severityRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(ObservationSeverity::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function snapshotNameRules(): array
    {
        return ['required', 'string', 'min:1', 'max:80'];
    }

    /**
     * Get the rules used to validate the profiles list filters.
     *
     * Every filter is optional, and a value that names nothing is treated as no
     * filter rather than as an error — see `BalanceProfileFilters`. The rules
     * here only keep the query string from carrying something absurd.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileFilterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:160'],
            'status' => $this->profileStatusRules(),
        ];
    }

    /**
     * Get the rules for the pair a snapshot comparison names.
     *
     * Both required, because a comparison against an implied default would make
     * the direction of every difference depend on an ordering the caller cannot
     * see.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function snapshotComparisonRules(): array
    {
        return [
            'from' => ['required', 'string'],
            'to' => ['required', 'string'],
        ];
    }
}
