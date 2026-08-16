<?php

namespace Modules\Playtesting\Application\Validation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Domain\Enums\ObservationCategory;
use Modules\Playtesting\Domain\Enums\PlaytestParticipantRole;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Domain\ValueObjects\FeedbackRating;

/**
 * The validation rules the module's form requests share.
 *
 * Gathered in one trait so that a limit has a single definition. The lengths
 * here are the same numbers the TypeScript schema uses to give somebody
 * immediate feedback as they type — the server's answer always wins, but the
 * two agreeing is what stops a form from accepting something the request will
 * then refuse.
 *
 * Nothing here decides ownership. Whether a version belongs to this game, a
 * participant to this session or an account to this workspace are questions
 * with their own rule classes beside this file, each of which resolves through
 * the same adapter the commands use.
 */
trait PlaytestValidationRules
{
    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function playtestTitleRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:3', 'max:160'];
    }

    /**
     * Get the rules for what a playtest set out to find out.
     *
     * Required, and with a floor under it. "Test the game" is not an
     * objective, and a playtest whose purpose nobody wrote down is one nobody
     * can interpret six months later.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function playtestObjectiveRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'min:10', 'max:2000'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function playtestHypothesisRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:2000'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function playtestConclusionRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:5000'];
    }

    /**
     * Get the rules for the version a playtest is testing.
     *
     * The ownership check is a rule object rather than an `exists` with a
     * `where`, so the "which versions belong to this game" question has one
     * definition — the one GameDesign publishes — instead of a second copy
     * written in a validator.
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
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function plannedAtRules(): array
    {
        return ['sometimes', 'nullable', 'date'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function sessionLocationRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:160'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function sessionNotesRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:10000'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function sessionOutcomeRules(): array
    {
        return ['sometimes', 'nullable', 'string', 'max:5000'];
    }

    /**
     * Get the rules for how a participant is referred to.
     *
     * Always required, including for participants with an account, because it
     * is what the session is read back with — somebody who introduced
     * themselves as "Sam" stays Sam here whatever their profile says later.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function displayNameRules(): array
    {
        return ['required', 'string', 'min:1', 'max:120'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function participantRoleRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(PlaytestParticipantRole::class)];
    }

    /**
     * Get the rules for linking a participant to a Barkeep account.
     *
     * Optional, because most participants have none. Restricted to the
     * workspace when given, because linking an account discloses its name and
     * address through the participant list.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function participantAccountRules(Game $game): array
    {
        return ['sometimes', 'nullable', 'string', new AccountIsOnTheTeam($game)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function joinedAtRules(): array
    {
        return ['sometimes', 'nullable', 'date'];
    }

    /**
     * Get the rules for attributing evidence to somebody at the table.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function participantReferenceRules(PlaytestSession $session): array
    {
        return ['sometimes', 'nullable', 'string', new ParticipantIsInSession($session)];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function observationCategoryRules(): array
    {
        return ['sometimes', 'nullable', Rule::enum(ObservationCategory::class)];
    }

    /**
     * Get the rules for what an observation says.
     *
     * The shortest useful observation is a few words — "market ignored" is a
     * real note somebody types mid-turn — so the floor is deliberately low.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function observationContentRules(): array
    {
        return ['required', 'string', 'min:3', 'max:5000'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function observedAtRules(): array
    {
        return ['sometimes', 'nullable', 'date'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function feedbackContentRules(): array
    {
        return ['required', 'string', 'min:3', 'max:5000'];
    }

    /**
     * Get the rules for a feedback score.
     *
     * The bounds come from the value object rather than being restated, so the
     * scale has one definition. Nullable is the important half: a participant
     * who did not put a number on their comment has not rated the game badly,
     * and a required field would force somebody to invent one.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function feedbackRatingRules(): array
    {
        return [
            'sometimes',
            'nullable',
            'integer',
            'min:'.FeedbackRating::MIN,
            'max:'.FeedbackRating::MAX,
        ];
    }

    /**
     * Get the validation rules used to validate the playtests list filters.
     *
     * Every filter is optional, and a value that names nothing is treated as
     * no filter rather than as an error — see `PlaytestFilters`. The rules
     * here only keep the query string from carrying something absurd.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function playtestFilterRules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', 'string', Rule::enum(PlaytestStatus::class)],
        ];
    }
}
