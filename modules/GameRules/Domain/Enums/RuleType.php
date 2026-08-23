<?php

namespace Modules\GameRules\Domain\Enums;

use Modules\GameRules\Domain\Enums\Contracts\Described;

/**
 * What part of the game a rule governs.
 *
 * Thirteen cases, and the number is the point. A taxonomy a designer can hold in
 * their head is one they will actually use; a hundred-case ontology is one they
 * will file everything under "other" to escape. Section 9 of the module brief
 * says to keep the initial list small, and this is that list.
 *
 * Classification only. Nothing in the module behaves differently because a rule
 * is a COMBAT rule rather than a MOVEMENT rule — the type is how a designer
 * finds it again in a tree of forty, and how the rulebook eventually groups
 * itself.
 *
 * Two cases do carry weight with the validator, and only because of what their
 * absence means: a rule set with nothing under {@see Setup} has no way to start,
 * and one with nothing under {@see Victory} has no way to finish. Both are
 * reported as findings rather than refused, because a half-written rule system
 * is full of them.
 */
enum RuleType: string implements Described
{
    case General = 'general';
    case Setup = 'setup';
    case Turn = 'turn';
    case Action = 'action';
    case Movement = 'movement';
    case Combat = 'combat';
    case Resource = 'resource';
    case Scoring = 'scoring';
    case Victory = 'victory';
    case Defeat = 'defeat';
    case EndGame = 'end_game';
    case PlayerInteraction = 'player_interaction';
    case Special = 'special';

    /**
     * The type a rule falls under when nobody chose one.
     */
    public static function default(): self
    {
        return self::General;
    }

    /**
     * A human readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::General => __('General'),
            self::Setup => __('Setup'),
            self::Turn => __('Turn'),
            self::Action => __('Action'),
            self::Movement => __('Movement'),
            self::Combat => __('Combat'),
            self::Resource => __('Resource'),
            self::Scoring => __('Scoring'),
            self::Victory => __('Victory'),
            self::Defeat => __('Defeat'),
            self::EndGame => __('End game'),
            self::PlayerInteraction => __('Player interaction'),
            self::Special => __('Special'),
        };
    }

    /**
     * The kind of rule that belongs under this heading.
     */
    public function description(): string
    {
        return match ($this) {
            self::General => __('Applies throughout, and belongs nowhere more specific.'),
            self::Setup => __('How the table is prepared before the first turn.'),
            self::Turn => __('The shape of a turn, and whose it is.'),
            self::Action => __('What a player may do, and what it takes.'),
            self::Movement => __('How pieces travel, and what stops them.'),
            self::Combat => __('How conflict is declared and resolved.'),
            self::Resource => __('How things are gained, held and spent.'),
            self::Scoring => __('How points are earned and counted.'),
            self::Victory => __('How the game is won.'),
            self::Defeat => __('How a player is knocked out.'),
            self::EndGame => __('What brings the game to a close.'),
            self::PlayerInteraction => __('Trading, negotiating, blocking, helping.'),
            self::Special => __('An exception, a variant, or a one-off.'),
        };
    }
}
