<?php

namespace Modules\GameRules\Domain\Enums;

use Modules\GameRules\Domain\Enums\Contracts\Described;

/**
 * What kind of stage of *play* a phase is.
 *
 * The name of this enum carries the module's sharpest distinction, so it is
 * worth stating plainly: a `GamePhase` is a stage of the game as it is played —
 * setup, a round, the action phase, cleanup. DesignFramework's `DesignPhase` is
 * a stage of the *designer's* work — ideation, prototyping, playtesting. The two
 * are unrelated and neither module knows the other exists.
 *
 * {@see EndGame} is the one case the graph builder treats specially: a phase of
 * that type is a terminal node, so the validator does not report it for having
 * no outgoing transition.
 */
enum GamePhaseType: string implements Described
{
    case Setup = 'setup';
    case Round = 'round';
    case Turn = 'turn';
    case Action = 'action';
    case Resolution = 'resolution';
    case Cleanup = 'cleanup';
    case EndGame = 'end_game';
    case Special = 'special';

    /**
     * The type a phase falls under when nobody chose one.
     */
    public static function default(): self
    {
        return self::Round;
    }

    /**
     * Determine whether play is meant to stop here.
     *
     * The graph builder draws these as terminal, and the validator does not ask
     * them for an outgoing transition — a phase the game ends in is supposed to
     * be a dead end.
     */
    public function isTerminal(): bool
    {
        return $this === self::EndGame;
    }

    /**
     * Determine whether play is meant to begin here.
     */
    public function isEntry(): bool
    {
        return $this === self::Setup;
    }

    /**
     * A human readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Setup => __('Setup'),
            self::Round => __('Round'),
            self::Turn => __('Turn'),
            self::Action => __('Action'),
            self::Resolution => __('Resolution'),
            self::Cleanup => __('Cleanup'),
            self::EndGame => __('End game'),
            self::Special => __('Special'),
        };
    }

    /**
     * What happens during a phase of this kind.
     */
    public function description(): string
    {
        return match ($this) {
            self::Setup => __('Preparing the table before play begins.'),
            self::Round => __('A full cycle that repeats until the game ends.'),
            self::Turn => __('One player acting, inside a round.'),
            self::Action => __('Where players spend what they have on what they do.'),
            self::Resolution => __('Working out the consequences of what was declared.'),
            self::Cleanup => __('Tidying, refilling and advancing markers.'),
            self::EndGame => __('Scoring and the final result. Play stops here.'),
            self::Special => __('A phase that only happens under some condition.'),
        };
    }
}
