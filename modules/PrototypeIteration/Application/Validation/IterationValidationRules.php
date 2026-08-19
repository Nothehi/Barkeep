<?php

namespace Modules\PrototypeIteration\Application\Validation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Domain\Enums\DesignChangeCategory;
use Modules\PrototypeIteration\Domain\Enums\EvidenceType;
use Modules\PrototypeIteration\Domain\Enums\IterationOutcome;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;
use Modules\PrototypeIteration\Domain\Enums\PrototypeArtifactType;
use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;
use Modules\PrototypeIteration\Domain\Enums\PrototypeType;
use Modules\PrototypeIteration\Domain\ValueObjects\ArtifactMetadata;

/**
 * The validation rules the module's form requests share.
 *
 * Gathered in one trait so that a limit has a single definition. The lengths here
 * are the same numbers the TypeScript schema uses to give somebody immediate
 * feedback as they type — the server's answer always wins, but the two agreeing is
 * what stops a form from accepting something the request will then refuse.
 *
 * Nothing here decides ownership. Whether a design version, a prototype version, a
 * playtest or a cited record belongs to this game are questions with their own rule
 * classes beside this file, each of which resolves through the same catalogue or
 * adapter the commands use.
 *
 * ## Why the floors are where they are
 *
 * Most minimums in this module are deliberately low, because most of this is typed
 * in a hurry: a change title is four words, an artifact name is a filename. Three
 * fields have real floors, and each is a field somebody will read in a year and
 * have to understand without asking anybody:
 *
 * - an iteration's objective, because "improve it" tells the next designer nothing;
 * - a change's reason, because an unexplained change is the one entry in a history
 *   that cannot be learned from;
 * - a decision's reason, because a decision without one is an instruction rather
 *   than an argument.
 *
 * Everything else is trusted to the person typing it.
 */
trait IterationValidationRules
{
    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function prototypeNameRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:2', 'max:160'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function prototypeDescriptionRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:5000'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function prototypeTypeRules(bool $required = false): array
    {
        return [$required ? 'required' : 'sometimes', 'nullable', Rule::enum(PrototypeType::class)];
    }

    /**
     * Get the rules for filtering a prototypes list by status.
     *
     * Only used on the list filter. A prototype's status is never set by a PATCH —
     * archiving is an action with its own endpoint, which is what keeps an
     * irreversible move from being one field value away from a reversible one.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function prototypeStatusRules(): array
    {
        return ['nullable', 'string', Rule::enum(PrototypeStatus::class)];
    }

    /**
     * Get the rules for naming a state of a prototype.
     *
     * Optional, and that is load-bearing rather than lax. The whole immutability
     * arrangement depends on a designer reaching for a new version instead of
     * editing the last one, and a version form that demanded fields would push them
     * the other way.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function prototypeVersionNameRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:160'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function prototypeVersionDescriptionRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:5000'];
    }

    /**
     * Get the rules for the design state something is based on.
     *
     * The ownership check is a rule object rather than an `exists` with a `where`,
     * so the "which versions belong to this game" question has one definition — the
     * one GameDesign publishes — instead of a second copy written in a validator.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function gameVersionRules(Game $game, bool $required = true): array
    {
        return [
            $required ? 'required' : 'sometimes',
            'string',
            new VersionBelongsToGame($game),
        ];
    }

    /**
     * Get the rules for the built state an iteration is about.
     *
     * The other half of the central invariant, and the half nothing outside this
     * module would catch — see {@see PrototypeVersionBelongsToGame}.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function prototypeVersionRules(Game $game, bool $required = true): array
    {
        return [
            $required ? 'required' : 'sometimes',
            'string',
            new PrototypeVersionBelongsToGame($game),
        ];
    }

    /**
     * Get the rules for an uploaded artifact.
     *
     * A size ceiling and nothing else, deliberately. There is no mime-type
     * allow-list because a prototype's assets are genuinely open-ended — an STL, a
     * Tabletop Simulator save, a spreadsheet, a font — and a list would refuse real
     * work while providing no safety, since nothing about the stored file is
     * trusted: the name is generated, the path is built by the storage adapter, and
     * the file is only ever streamed back as an attachment.
     *
     * The ceiling is generous because a print-ready card sheet at 300dpi genuinely
     * runs to tens of megabytes, and a limit that refused one would make the
     * feature useless for the commonest case.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function artifactFileRules(): array
    {
        return ['required', 'file', 'max:'.ArtifactMetadata::MAX_SIZE_KILOBYTES];
    }

    /**
     * Get the rules for what an artifact is called.
     *
     * Optional, because it is derived from the uploaded filename when absent —
     * somebody dragging in four print sheets should not have to name each one.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function artifactNameRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:255'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function artifactTypeRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(PrototypeArtifactType::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function iterationTitleRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:3', 'max:160'];
    }

    /**
     * Get the rules for what an iteration set out to change.
     *
     * Required, with a floor under it. "Improve the game" is not an objective, and a
     * cycle whose purpose nobody wrote down is one nobody can interpret when they
     * come back to it.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function iterationObjectiveRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:10', 'max:2000'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function iterationHypothesisRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:2000'];
    }

    /**
     * Get the rules for how a cycle turned out.
     *
     * Required, not nullable, and no default. An outcome that fell back to something
     * would record the platform's guess as the studio's own judgement.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function iterationOutcomeRules(): array
    {
        return ['required', Rule::enum(IterationOutcome::class)];
    }

    /**
     * Get the rules for what a cycle taught the designer.
     *
     * Required alongside the outcome, with a floor, because the outcome is an index
     * and this is the account. "Partial" on its own tells the next designer where
     * they are but not where to start.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function iterationSummaryRules(): array
    {
        return ['required', 'string', 'min:10', 'max:5000'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function changeTitleRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:3', 'max:200'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function changeDescriptionRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:5000'];
    }

    /**
     * Get the rules for why a change was made.
     *
     * The only required prose on a change beyond its title, and the field the whole
     * record exists for. A list of edits answers "what is different"; a list of
     * edits with reasons answers "why is the game like this", which is the question
     * somebody actually has eighteen months later.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function changeReasonRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:5', 'max:5000'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function changeCategoryRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(DesignChangeCategory::class)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function experimentTitleRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:3', 'max:200'];
    }

    /**
     * Get the rules for the question an experiment asks.
     *
     * The one required field on the before half. Everything else about an experiment
     * is optional because exploratory work is real work — but an experiment with no
     * question is not an experiment, it is a session.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function experimentQuestionRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:5', 'max:2000'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function experimentProseRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:2000'];
    }

    /**
     * Get the rules for what an experiment actually produced.
     *
     * Required to complete one, because an experiment's whole value is its result.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function experimentResultRules(): array
    {
        return ['required', 'string', 'min:5', 'max:5000'];
    }

    /**
     * Get the rules for what an experiment's result means.
     *
     * Optional, unlike the result beside it. What happened is a fact the person who
     * ran the session already has; what it means often arrives days later, and
     * demanding it in the same request would produce conclusions written to fill a
     * field.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function experimentConclusionRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:5000'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function decisionTitleRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:3', 'max:200'];
    }

    /**
     * Get the rules for what was decided.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function decisionStatementRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:5', 'max:5000'];
    }

    /**
     * Get the rules for why it was decided.
     *
     * Required and floored, for the reason set out above the trait: a decision
     * without a reason is an instruction, and an instruction cannot be re-examined
     * when the situation changes.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function decisionReasonRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:5', 'max:5000'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function evidenceTypeRules(): array
    {
        return ['required', Rule::enum(EvidenceType::class)];
    }

    /**
     * Get the rules for what a citation points at.
     *
     * Nullable here and required by the rule object, which is not a contradiction: whether a reference
     * is needed depends on the type, and expressing that in the rule that already knows the type keeps
     * one message on the form instead of two. The rule declares itself implicit so it still runs when
     * the field is absent — see {@see EvidenceReferenceIsResolvable}.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function evidenceReferenceRules(Game $game, ?EvidenceType $type): array
    {
        return ['nullable', 'string', new EvidenceReferenceIsResolvable($game, $type)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function evidenceDescriptionRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:2000'];
    }

    /**
     * Get the rules for the playtest an iteration was tested through.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function playtestRules(Game $game): array
    {
        return ['required', 'string', new PlaytestBelongsToGame($game)];
    }

    /**
     * Get the rules for the design state a new game version is cut with.
     *
     * Both optional, because cutting the next version from an iteration is a
     * deliberate act with nothing required to say about it — GameDesign allocates
     * the number, and a designer who wants to name it can.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function nextGameVersionRules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:160'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Get the rules used to validate the prototypes list filters.
     *
     * Every filter is optional, and a value that names nothing is treated as no
     * filter rather than as an error — see `PrototypeFilters`. The rules here only
     * keep the query string from carrying something absurd.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function prototypeFilterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:160'],
            'status' => $this->prototypeStatusRules(),
            'type' => ['nullable', 'string', Rule::enum(PrototypeType::class)],
        ];
    }

    /**
     * Get the rules used to validate the iterations list filters.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function iterationFilterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', 'string', Rule::enum(IterationStatus::class)],
            'outcome' => ['nullable', 'string', Rule::enum(IterationOutcome::class)],
            'prototype' => ['nullable', 'string'],
        ];
    }
}
