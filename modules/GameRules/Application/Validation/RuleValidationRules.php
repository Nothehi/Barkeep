<?php

namespace Modules\GameRules\Application\Validation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Modules\GameRules\Domain\Enums\ConditionOperator;
use Modules\GameRules\Domain\Enums\ConditionType;
use Modules\GameRules\Domain\Enums\EffectType;
use Modules\GameRules\Domain\Enums\GamePhaseType;
use Modules\GameRules\Domain\Enums\LogicOperator;
use Modules\GameRules\Domain\Enums\MechanicCategory;
use Modules\GameRules\Domain\Enums\ReferenceType;
use Modules\GameRules\Domain\Enums\RequirementType;
use Modules\GameRules\Domain\Enums\RuleActionType;
use Modules\GameRules\Domain\Enums\RuleSetStatus;
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\Enums\RuleType;
use Modules\GameRules\Domain\Enums\TriggerType;
use Modules\GameRules\Domain\Models\RuleSet;

/**
 * The validation rules the module's form requests share.
 *
 * Gathered in one trait so that a limit has a single definition. The lengths here
 * are the same numbers the TypeScript schema uses to give somebody immediate
 * feedback as they type — the server's answer always wins, but the two agreeing
 * is what stops a form from accepting something the request will then refuse.
 *
 * Nothing here decides ownership. Whether a phase, rule, action, condition or
 * trigger belongs to this rule set are questions with their own rule classes
 * beside this file, each of which resolves through the same catalogue the
 * commands use.
 *
 * ## Why the value fields are strings
 *
 * A condition's value, a requirement's threshold and an effect's amount are all
 * `string` with no numeric rule. That is not laziness: "+3", "half, rounded down"
 * and "all of them" are things a rulebook says, and nothing in this module
 * computes with any of them. Where a number *is* expected — an "is at least"
 * compared against text — the validator reports it as a finding, which is the
 * right level of insistence for something somebody is halfway through typing.
 *
 * ## Why the floors are where they are
 *
 * Most minimums are deliberately low, because most of this is written
 * mid-thought. Two fields have real floors, and each is one somebody will read in
 * a year and have to understand without asking anybody: a requirement's
 * description, because a gate nobody can read is not a gate, and a rule set's
 * name, because "v2" beside four other rule sets tells nobody anything.
 */
trait RuleValidationRules
{
    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function ruleSetNameRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:2', 'max:160'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function ruleSetStatusRules(): array
    {
        return ['nullable', 'string', Rule::enum(RuleSetStatus::class)];
    }

    /**
     * Get the rules for a name that becomes a handle.
     *
     * Floored at one character rather than two, unlike a rule set's: a phase
     * called "A" is unhelpful but is somebody's shorthand, and this module should
     * not be the thing that argues with them about it.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(bool $required = true, int $max = 120): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:1', 'max:'.$max];
    }

    /**
     * Get the rules for a name a record is identified by rather than slugged from.
     *
     * Conditions, groups, triggers and the three outcomes. Floored at two, because
     * these are referred to in prose — a transition guarded by "x" reads worse
     * than one guarded by nothing.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function statementNameRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:2', 'max:160'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function descriptionRules(int $max = 10000): array
    {
        return ['sometimes', 'nullable', 'string', 'max:'.$max];
    }

    /**
     * Get the rules for what a requirement says.
     *
     * Required and floored, because a gate nobody can read is not a gate — and
     * because this is prose rather than an expression, it is the only place the
     * rule itself lives. See section 17 of the module brief.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function requirementDescriptionRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:3', 'max:2000'];
    }

    /**
     * Get the rules for what an effect acts on.
     *
     * Free text and required. The things an effect targets are not all rows — a
     * board position is not a record anywhere — so there is nothing to validate it
     * against beyond it being said at all.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function effectTargetRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:1', 'max:160'];
    }

    /**
     * Get the rules for a declarative value.
     *
     * A string with no numeric rule, deliberately. See the note at the top of
     * this trait.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function valueRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:255'];
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
     * Get the rules for which outcome is checked first.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function priorityRules(): array
    {
        return ['sometimes', 'integer', 'min:0', 'max:100000'];
    }

    /**
     * Get the rules for a whole reordered list.
     *
     * The whole list rather than one id and an index, which is the shape a drag
     * produces and the only shape that cannot go half-wrong.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function orderRules(string $field): array
    {
        return [
            $field => ['required', 'array', 'max:1000'],
            $field.'.*' => ['required', 'string'],
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function ruleTypeRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(RuleType::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function ruleStatusRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(RuleStatus::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function mechanicCategoryRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(MechanicCategory::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function phaseTypeRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(GamePhaseType::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function actionTypeRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(RuleActionType::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function requirementTypeRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(RequirementType::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function conditionTypeRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(ConditionType::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function operatorRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(ConditionOperator::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function logicOperatorRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(LogicOperator::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function effectTypeRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(EffectType::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function triggerTypeRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(TriggerType::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function referenceTypeRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(ReferenceType::class)];
    }

    /**
     * Get the rules for a phase named in a request body.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function phaseReferenceRules(RuleSet $ruleSet, bool $required = false): array
    {
        return $required
            ? ['required', 'string', new PhaseBelongsToRuleSet($ruleSet)]
            : ['sometimes', 'nullable', 'string', new PhaseBelongsToRuleSet($ruleSet)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function ruleReferenceRules(RuleSet $ruleSet, bool $required = false): array
    {
        return $required
            ? ['required', 'string', new RuleBelongsToRuleSet($ruleSet)]
            : ['sometimes', 'nullable', 'string', new RuleBelongsToRuleSet($ruleSet)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function actionReferenceRules(RuleSet $ruleSet, bool $required = false): array
    {
        return $required
            ? ['required', 'string', new ActionBelongsToRuleSet($ruleSet)]
            : ['sometimes', 'nullable', 'string', new ActionBelongsToRuleSet($ruleSet)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function conditionReferenceRules(RuleSet $ruleSet, bool $required = false): array
    {
        return $required
            ? ['required', 'string', new ConditionBelongsToRuleSet($ruleSet)]
            : ['sometimes', 'nullable', 'string', new ConditionBelongsToRuleSet($ruleSet)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function triggerReferenceRules(RuleSet $ruleSet, bool $required = false): array
    {
        return $required
            ? ['required', 'string', new TriggerBelongsToRuleSet($ruleSet)]
            : ['sometimes', 'nullable', 'string', new TriggerBelongsToRuleSet($ruleSet)];
    }

    /**
     * Get the rules for a handle pointing into the game's economy.
     *
     * A plain string, deliberately unvalidated against anything. Resolving it here
     * would mean the form request importing GameEconomy, and once it can do that
     * the module ends up holding a copy of the cost rather than a pointer to it.
     * An unresolved handle is a finding, not a refusal — see section 34 of the
     * brief.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function economyHandleRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:80'];
    }

    /**
     * Get the rules used to validate the rule sets list filters.
     *
     * Every filter is optional, and a value that names nothing is treated as no
     * filter rather than as an error — see `RuleSetFilters`.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function ruleSetFilterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:160'],
            'status' => $this->ruleSetStatusRules(),
        ];
    }
}
