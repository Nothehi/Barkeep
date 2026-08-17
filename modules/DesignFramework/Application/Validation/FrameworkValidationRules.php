<?php

namespace Modules\DesignFramework\Application\Validation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Modules\DesignFramework\Domain\Enums\CriterionRating;
use Modules\DesignFramework\Domain\Enums\FrameworkContentStatus;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\ValueObjects\ContentSlug;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkSlug;

/**
 * The validation rules the module's form requests share.
 *
 * Gathered in one trait so that a limit has a single definition. The lengths here are the
 * same numbers the TypeScript schema uses to give somebody immediate feedback as they type —
 * the server's answer always wins, but the two agreeing is what stops a form from accepting
 * something the request will then refuse.
 *
 * Nothing here decides ownership or immutability. Whether a phase belongs to this version,
 * whether a version may still be edited, and whether a criterion belongs to the version a
 * game adopted are all questions with their own homes: `ContentWriter`,
 * `FrameworkModificationGuard` and `FrameworkContentLocator` respectively. Duplicating any
 * of them as a validation rule would be a second implementation of an invariant.
 */
trait FrameworkValidationRules
{
    /**
     * Get the rules for a framework's name.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function frameworkNameRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:3', 'max:160'];
    }

    /**
     * Get the rules for a framework's address.
     *
     * Optional on creation, because the command derives one from the name. Uniqueness is not
     * checked here: the address is allocated against a unique index and a `unique` rule
     * would be a second, racier answer to the same question.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function frameworkSlugRules(): array
    {
        return [
            'sometimes',
            'nullable',
            'string',
            'min:'.FrameworkSlug::MIN_LENGTH,
            'max:'.FrameworkSlug::MAX_LENGTH,
            new IsAFrameworkSlug,
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function frameworkDescriptionRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:5000'];
    }

    /**
     * Get the rules for filtering the frameworks list.
     *
     * Every filter is optional, and a value that names nothing is treated as no filter
     * rather than as an error — see `FrameworkFilters`. The rules here only keep the query
     * string from carrying something absurd.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function frameworkFilterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', 'string', Rule::enum(FrameworkStatus::class)],
        ];
    }

    /**
     * Get the rules for a version's name.
     *
     * Optional in both directions: a version is cited by number, and a name is for the
     * editions that earned one.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function versionNameRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:160'];
    }

    /**
     * Get the rules for a phase's name.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function phaseNameRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:2', 'max:160'];
    }

    /**
     * Get the rules for the title of a piece of content.
     *
     * Longer than a name's ceiling because criteria and prompts are written as whole
     * questions — "does player interaction create interesting choices?" is a title.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function contentTitleRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:2', 'max:240'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function contentDescriptionRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:5000'];
    }

    /**
     * Get the rules for how to carry out a practice.
     *
     * Longer than a description, because instructions are followed rather than skimmed and
     * "run a two-player test" deserves as many words as it takes.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function instructionsRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:10000'];
    }

    /**
     * Get the rules for the question a prompt asks.
     *
     * Required, because a prompt with no question is nothing. The only mandatory body field
     * among the five content types.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function promptRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:5', 'max:2000'];
    }

    /**
     * Get the rules for filing content under a phase.
     *
     * The ownership check is a rule object rather than an `exists` with a `where`, so "which
     * phases belong to this version" has one definition. Nullable is the important half: a
     * null phase means the content applies across the whole methodology, which is a real
     * choice rather than a missing value.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function phaseReferenceRules(FrameworkVersion $version): array
    {
        return ['sometimes', 'nullable', 'string', new PhaseBelongsToVersion($version)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function contentStatusRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(FrameworkContentStatus::class)];
    }

    /**
     * Get the rules for the design fact that answers a piece of content.
     *
     * Nullable, because almost nothing carries one: a judgement criterion is
     * graded by the designer and names no fact. Sending null is how an author
     * detaches one and hands the question back.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function designFactRules(): array
    {
        return ['sometimes', 'nullable', 'string', app(IsADesignFact::class)];
    }

    /**
     * Get the rules for a requested position.
     *
     * Only bounded from below here. The upper bound depends on how many siblings the item
     * actually has, which is `Position::within()`'s job — it raises a domain violation
     * reported against this same field, so the form still shows it in place.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function positionRules(): array
    {
        return ['required', 'integer', 'min:1'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function requiredFlagRules(): array
    {
        return ['sometimes', 'boolean'];
    }

    /**
     * Get the rules for the address of a checklist item's title.
     *
     * Content addresses are derived rather than typed, so this only exists to keep a
     * hand-written one from reaching the allocator with something unusable.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function contentSlugRules(): array
    {
        return [
            'sometimes',
            'nullable',
            'string',
            'min:'.ContentSlug::MIN_LENGTH,
            'max:'.ContentSlug::MAX_LENGTH,
        ];
    }

    /**
     * Get the rules for grading a game against a criterion.
     *
     * "Not evaluated" is excluded: it is the state a criterion is in before anybody acts, and
     * accepting it would make clearing an assessment look like making one.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function criterionRatingRules(): array
    {
        return [
            'required',
            'string',
            Rule::enum(CriterionRating::class)->only(CriterionRating::grades()),
        ];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function notesRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:5000'];
    }

    /**
     * Get the rules for ticking or unticking something.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function completedFlagRules(): array
    {
        return ['sometimes', 'boolean'];
    }

    /**
     * Get the rules for answering a prompt.
     *
     * Generous, because this is where a designer writes down what their game is actually
     * about and a limit that cut them off mid-thought would be the wrong kind of opinion.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function responseRules(): array
    {
        return ['required', 'string', 'min:1', 'max:20000'];
    }
}
