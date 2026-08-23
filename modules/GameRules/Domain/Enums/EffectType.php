<?php

namespace Modules\GameRules\Domain\Enums;

use Modules\GameRules\Domain\Enums\Contracts\Described;

/**
 * What happens once a rule or an action resolves.
 *
 * An effect is a structured sentence — type, target, value — and nothing here is
 * executable. Section 21 of the brief refuses arbitrary code for the same reason
 * section 17 refuses an expression language: this module describes a board game,
 * and the thing that eventually *plays* one is a separate bounded context.
 *
 * So "RESOURCE / Wood / -5" is a record of what the rulebook says, not an
 * instruction anything will carry out. The number five, if the studio wants it
 * to be authoritative, belongs to GameEconomy — this module points at it rather
 * than owning it.
 *
 * {@see PhaseChange} and {@see EndGame} are the two cases the graph builder
 * reads: an effect of either kind is a way play moves that is not a phase
 * transition, and drawing the flow without them would show a game that never
 * finishes.
 */
enum EffectType: string implements Described
{
    case Resource = 'resource';
    case Movement = 'movement';
    case Draw = 'draw';
    case Discard = 'discard';
    case Score = 'score';
    case Damage = 'damage';
    case Heal = 'heal';
    case StateChange = 'state_change';
    case Unlock = 'unlock';
    case Lock = 'lock';
    case TurnChange = 'turn_change';
    case PhaseChange = 'phase_change';
    case EndGame = 'end_game';
    case Custom = 'custom';

    /**
     * The type an effect falls under when nobody chose one.
     */
    public static function default(): self
    {
        return self::Custom;
    }

    /**
     * Determine whether the effect is meaningless without a quantity.
     *
     * Gaining wood without saying how much is not an effect anybody can play
     * with; unlocking an ability is complete as it stands. The validator reports
     * the first shape and leaves the second alone.
     */
    public function expectsValue(): bool
    {
        return in_array($this, [
            self::Resource,
            self::Draw,
            self::Discard,
            self::Score,
            self::Damage,
            self::Heal,
        ], strict: true);
    }

    /**
     * Determine whether the effect can name a GameEconomy record.
     *
     * The three that move quantities of something the economy already models.
     * The reference is a handle rather than a foreign key — see section 34 of the
     * brief, and `EconomyReference`.
     */
    public function isEconomic(): bool
    {
        return in_array($this, [self::Resource, self::Score, self::Damage], strict: true);
    }

    /**
     * Determine whether the effect moves play somewhere else.
     *
     * Read by the graph builder, which draws these alongside phase transitions
     * so the flow of a game shows every way it can advance.
     */
    public function movesPlay(): bool
    {
        return in_array($this, [self::TurnChange, self::PhaseChange, self::EndGame], strict: true);
    }

    /**
     * A human readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Resource => __('Resource'),
            self::Movement => __('Movement'),
            self::Draw => __('Draw'),
            self::Discard => __('Discard'),
            self::Score => __('Score'),
            self::Damage => __('Damage'),
            self::Heal => __('Heal'),
            self::StateChange => __('State change'),
            self::Unlock => __('Unlock'),
            self::Lock => __('Lock'),
            self::TurnChange => __('Turn change'),
            self::PhaseChange => __('Phase change'),
            self::EndGame => __('End game'),
            self::Custom => __('Custom'),
        };
    }

    /**
     * What an effect of this kind does.
     */
    public function description(): string
    {
        return match ($this) {
            self::Resource => __('Gain or lose an amount of something.'),
            self::Movement => __('Move a piece, a worker or a marker.'),
            self::Draw => __('Take cards from a deck.'),
            self::Discard => __('Put cards away.'),
            self::Score => __('Change a player\'s score.'),
            self::Damage => __('Take health, structure or morale away.'),
            self::Heal => __('Give some of it back.'),
            self::StateChange => __('Flip a flag: exhausted, revealed, blocked.'),
            self::Unlock => __('Make something available that was not.'),
            self::Lock => __('Take something away that was.'),
            self::TurnChange => __('End the turn, or give it to somebody else.'),
            self::PhaseChange => __('Move play into another phase.'),
            self::EndGame => __('Bring the game to a close.'),
            self::Custom => __('Anything else, written out in words.'),
        };
    }
}
