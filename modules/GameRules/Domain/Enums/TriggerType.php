<?php

namespace Modules\GameRules\Domain\Enums;

use Modules\GameRules\Domain\Enums\Contracts\Described;

/**
 * When something happens without anybody choosing it.
 *
 * A trigger is the "when" half of an automatic rule: at the start of a round,
 * when a player reaches ten points, when the deck runs out. This module records
 * that the trigger exists and what it is attached to; it never fires one.
 *
 * Section 23 of the brief is explicit about that and it is the line most likely
 * to be crossed by accident. A trigger with an effect looks like something that
 * wants to be run, and the first `if` written to run it is the first line of a
 * game engine living inside a design tool. The engine is a separate bounded
 * context — `GameRuntime` — and it does not exist yet.
 */
enum TriggerType: string implements Described
{
    case GameStart = 'game_start';
    case RoundStart = 'round_start';
    case RoundEnd = 'round_end';
    case TurnStart = 'turn_start';
    case TurnEnd = 'turn_end';
    case PhaseStart = 'phase_start';
    case PhaseEnd = 'phase_end';
    case ActionExecuted = 'action_executed';
    case ConditionMet = 'condition_met';
    case ResourceChanged = 'resource_changed';
    case ScoreChanged = 'score_changed';
    case PlayerEvent = 'player_event';
    case GameStateChanged = 'game_state_changed';
    case Custom = 'custom';

    /**
     * The type a trigger falls under when nobody chose one.
     */
    public static function default(): self
    {
        return self::Custom;
    }

    /**
     * Determine whether the trigger fires off a phase boundary.
     *
     * Read by the graph builder: a transition guarded by one of these is drawn
     * on the phase boundary rather than as a condition inside it.
     */
    public function isPhaseBoundary(): bool
    {
        return in_array($this, [
            self::PhaseStart,
            self::PhaseEnd,
            self::RoundStart,
            self::RoundEnd,
            self::TurnStart,
            self::TurnEnd,
        ], strict: true);
    }

    /**
     * A human readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::GameStart => __('Game start'),
            self::RoundStart => __('Round start'),
            self::RoundEnd => __('Round end'),
            self::TurnStart => __('Turn start'),
            self::TurnEnd => __('Turn end'),
            self::PhaseStart => __('Phase start'),
            self::PhaseEnd => __('Phase end'),
            self::ActionExecuted => __('Action taken'),
            self::ConditionMet => __('Condition met'),
            self::ResourceChanged => __('Resource changed'),
            self::ScoreChanged => __('Score changed'),
            self::PlayerEvent => __('Player event'),
            self::GameStateChanged => __('Game state changed'),
            self::Custom => __('Custom'),
        };
    }

    /**
     * When a trigger of this kind fires.
     */
    public function description(): string
    {
        return match ($this) {
            self::GameStart => __('Once, as the game begins.'),
            self::RoundStart => __('At the top of every round.'),
            self::RoundEnd => __('As each round closes.'),
            self::TurnStart => __('When a player\'s turn begins.'),
            self::TurnEnd => __('When a player\'s turn is over.'),
            self::PhaseStart => __('On entering a phase.'),
            self::PhaseEnd => __('On leaving a phase.'),
            self::ActionExecuted => __('Whenever a particular action is taken.'),
            self::ConditionMet => __('The moment a named condition becomes true.'),
            self::ResourceChanged => __('When an amount a player holds goes up or down.'),
            self::ScoreChanged => __('When somebody\'s score moves.'),
            self::PlayerEvent => __('When something happens to a player: elimination, promotion.'),
            self::GameStateChanged => __('When a flag about the game as a whole flips.'),
            self::Custom => __('Anything else, written out in words.'),
        };
    }
}
