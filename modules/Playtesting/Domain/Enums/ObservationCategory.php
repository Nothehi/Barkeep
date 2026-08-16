<?php

namespace Modules\Playtesting\Domain\Enums;

/**
 * What part of the design an observation is about.
 *
 * The taxonomy is deliberately short. Its whole job today is to make a session
 * readable back — "everything I noticed about the rules" — and a list somebody
 * has to scroll during a live session is a list they will stop using. Eight
 * choices fit on a screen and are picked without thinking.
 *
 * This is the version that will be replaced. When the framework system arrives
 * it will own a configurable taxonomy, and these cases become its seed rather
 * than its ceiling — which is why {@see Other} exists from the start, so
 * nobody is forced to file something under the wrong heading in the meantime.
 */
enum ObservationCategory: string
{
    case Rules = 'rules';
    case Gameplay = 'gameplay';
    case PlayerBehavior = 'player_behavior';
    case Balance = 'balance';
    case Ux = 'ux';
    case Pacing = 'pacing';
    case Components = 'components';
    case Other = 'other';

    /**
     * The category an observation falls into when nobody chose one.
     */
    public static function default(): self
    {
        return self::Other;
    }

    /**
     * A human readable label for the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::Rules => __('Rules'),
            self::Gameplay => __('Gameplay'),
            self::PlayerBehavior => __('Player behaviour'),
            self::Balance => __('Balance'),
            self::Ux => __('Usability'),
            self::Pacing => __('Pacing'),
            self::Components => __('Components'),
            self::Other => __('Other'),
        };
    }

    /**
     * The kind of thing that belongs under this heading.
     *
     * Written as examples rather than as definitions, because a designer
     * picking a category mid-session recognises an example faster than they
     * parse a definition.
     */
    public function description(): string
    {
        return match ($this) {
            self::Rules => __('A rule was misread, missed or argued about.'),
            self::Gameplay => __('Something about how the game actually played out.'),
            self::PlayerBehavior => __('What players did, chose or avoided.'),
            self::Balance => __('A strategy, position or resource that was too strong or too weak.'),
            self::Ux => __('Something was hard to see, reach or understand.'),
            self::Pacing => __('The game dragged, rushed or stalled.'),
            self::Components => __('A card, board, token or piece of the physical game.'),
            self::Other => __('Anything that does not fit the categories above.'),
        };
    }
}
