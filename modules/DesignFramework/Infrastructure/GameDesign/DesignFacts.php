<?php

namespace Modules\DesignFramework\Infrastructure\GameDesign;

use Modules\GameDesign\Application\Queries\GetDesignRecord;
use Modules\GameDesign\Domain\Models\DesignRecord;
use Modules\GameDesign\Domain\Models\Game;

/**
 * The one place this module reads a game's design.
 *
 * A methodology asks two kinds of question. "Is the core decision meaningful?"
 * is a judgement, and nothing but a designer can answer it — those keep the
 * four-point scale. "Are the player count and playing time decided?" is a
 * question about whether a fact has been written down, and asking somebody to
 * grade themselves on it was always the wrong shape: they ticked "player count
 * decided" on their own word while the platform had no idea whether it was.
 *
 * This class is what makes the second kind answerable. Framework content names
 * a fact in its `satisfied_by` column; this maps that name to a way of reading
 * the game's design record, and to the wording a screen shows.
 *
 * ## Why it is the only place
 *
 * The same reason `GameAccess` is the only place this module asks the gate about
 * a game. DesignFramework sits on top of GameDesign and reads it through the
 * queries GameDesign publishes; scattering `$record->player_count_min` through a
 * calculator, two commands and three resources would be five places to update
 * when a field is renamed, and five chances for one of them to disagree about
 * whether whitespace counts as an answer.
 *
 * The dependency runs one way and is held by architecture tests on both sides:
 * DesignFramework may read GameDesign, and GameDesign must never learn that
 * methodologies exist.
 *
 * ## What is deliberately not here
 *
 * Any notion of a fact being *good*. A fact is recorded or it is not. Whether
 * two to four players is the right answer for this game is a judgement, and the
 * moment this class started having opinions about that it would be a worse
 * version of the thing it replaced.
 */
final class DesignFacts
{
    /**
     * The facts framework content may be answered by.
     *
     * The key is what a framework author stores; the label is what a designer
     * reads. Adding a fact means adding a line here and nothing else — which is
     * the point of the indirection, because the alternative is a framework
     * author writing a column name into a form.
     *
     * @var array<string, string>
     */
    private const FACTS = [
        'pitch' => 'the one-sentence pitch',
        'player_count' => 'the player count',
        'play_time' => 'the playing time',
        'target_age' => 'the youngest player',
        'complexity' => 'the intended weight',
        'audience' => 'the intended audience',
        'mechanics' => 'at least one mechanic',
        'core_action' => 'the core action',
        'core_cost' => 'what the core action costs',
        'core_reward' => 'what the core action gives back',
        'win_condition' => 'the win condition',
        'failure_condition' => 'the failure condition',
        'core_loop' => 'the whole core loop',
    ];

    public function __construct(private readonly GetDesignRecord $records) {}

    /**
     * The fact keys a framework author may choose from.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys(self::FACTS);
    }

    /**
     * Determine whether the given key names a fact this module can read.
     *
     * Used by validation, so a builder cannot store a key nothing will ever
     * answer — which would be content that silently never completes.
     */
    public function knows(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::FACTS);
    }

    /**
     * What a designer is being asked to write down.
     *
     * Phrased to complete "Record …", so a screen can say "Record the player
     * count" without assembling a sentence out of a column name.
     */
    public function label(string $key): string
    {
        return self::FACTS[$key] ?? $key;
    }

    /**
     * The facts, as options for the builder.
     *
     * @return list<array{value: string, label: string}>
     */
    public function options(): array
    {
        return array_map(
            fn (string $key): array => ['value' => $key, 'label' => self::FACTS[$key]],
            $this->keys(),
        );
    }

    /**
     * Read a game's design record once.
     *
     * Handed to `recorded()` for each piece of content, rather than each call
     * fetching it again. A phase page asks this question of every criterion and
     * every checklist item on it, and the record does not change in between.
     */
    public function recordFor(Game $game): ?DesignRecord
    {
        return $this->records->handle($game);
    }

    /**
     * Every fact, and whether this game has written it down.
     *
     * Sent to the screens as one map rather than folded into each criterion,
     * which keeps the module's central separation intact all the way to the
     * client: the criterion is the methodology's and is the same for every game,
     * and what this game has recorded is its own. The client joins them by the
     * fact key, exactly as it joins evaluations to criteria by id.
     *
     * @return array<string, bool>
     */
    public function recordedMap(?DesignRecord $record): array
    {
        $recorded = [];

        foreach ($this->keys() as $key) {
            $recorded[$key] = $this->recorded($record, $key);
        }

        return $recorded;
    }

    /**
     * Determine whether the named fact has been written down.
     *
     * A game with no record has decided nothing, so every fact is unrecorded —
     * which is the honest answer and the one that keeps a fresh game's progress
     * at zero rather than at "nothing to do".
     *
     * Whitespace is not an answer. `DesignRecord::isBlank()` is what decides
     * that, so a field holding a space cannot satisfy a criterion here while
     * looking empty on the settings screen.
     */
    public function recorded(?DesignRecord $record, string $key): bool
    {
        if ($record === null) {
            return false;
        }

        return match ($key) {
            'pitch' => ! $record->isBlank('pitch'),
            'player_count' => $record->playerCount() !== null,
            'play_time' => $record->playTime() !== null,
            'target_age' => $record->target_age_min !== null,
            'complexity' => $record->complexity !== null,
            'audience' => ! $record->isBlank('audience'),
            'mechanics' => $record->mechanics->isNotEmpty(),
            'core_action' => ! $record->isBlank('core_action'),
            'core_cost' => ! $record->isBlank('core_cost'),
            'core_reward' => ! $record->isBlank('core_reward'),
            'win_condition' => ! $record->isBlank('win_condition'),
            'failure_condition' => ! $record->isBlank('failure_condition'),
            'core_loop' => $record->hasCompleteCoreLoop(),

            /*
             * A key this module does not know cannot be answered. Reported as
             * unrecorded rather than raising, because the alternative is a
             * mistyped key in seeded content taking down every phase page that
             * shows it — and `knows()` is what stops one being stored.
             */
            default => false,
        };
    }
}
