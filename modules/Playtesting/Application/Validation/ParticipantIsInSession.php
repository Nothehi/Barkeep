<?php

namespace Modules\Playtesting\Application\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;

/**
 * Prove that an attributed participant was actually at the session.
 *
 * A participant id is the one identifier in the module that arrives in a
 * request body without a route binding to scope it, so it is the one that
 * could name somebody from another session entirely.
 *
 * Getting it wrong would be worse than a leak: attaching one session's
 * feedback to another session's participant produces a record that reads
 * perfectly and is false, and nobody would ever have reason to check it.
 */
class ParticipantIsInSession implements ValidationRule
{
    public function __construct(private readonly PlaytestSession $session) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $participant = app(PlaytestRepository::class)
            ->findParticipantInSession($this->session, $value);

        if ($participant === null) {
            $fail(__('That participant is not part of this session.'));
        }
    }
}
