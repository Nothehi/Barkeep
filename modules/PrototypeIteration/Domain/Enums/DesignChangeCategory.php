<?php

namespace Modules\PrototypeIteration\Domain\Enums;

/**
 * What part of the design a change touched.
 *
 * The taxonomy is kept small on purpose, and it is the second short list in
 * the platform after Playtesting's observation categories. The two are
 * deliberately not the same list: an observation is filed by what somebody
 * *noticed*, a change by what the designer *edited*, and collapsing them would
 * make the obvious question — "we noticed pacing problems; what did we change
 * about pacing?" — impossible to answer honestly.
 *
 * Ten choices fit on a screen and are picked without thinking, which is the
 * whole requirement: a designer writing down four changes after a session will
 * abandon a list they have to scroll.
 */
enum DesignChangeCategory: string
{
    case Rules = 'rules';
    case Mechanics = 'mechanics';
    case Balance = 'balance';
    case Components = 'components';
    case PlayerInteraction = 'player_interaction';
    case Pacing = 'pacing';
    case Ux = 'ux';
    case Theme = 'theme';
    case Economy = 'economy';
    case Other = 'other';

    /**
     * The category a change falls into when nobody chose one.
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
            self::Mechanics => __('Mechanics'),
            self::Balance => __('Balance'),
            self::Components => __('Components'),
            self::PlayerInteraction => __('Player interaction'),
            self::Pacing => __('Pacing'),
            self::Ux => __('Usability'),
            self::Theme => __('Theme'),
            self::Economy => __('Economy'),
            self::Other => __('Other'),
        };
    }

    /**
     * The kind of edit that belongs under this heading.
     */
    public function description(): string
    {
        return match ($this) {
            self::Rules => __('How a rule is written or when it applies.'),
            self::Mechanics => __('A system was added, removed or reshaped.'),
            self::Balance => __('A cost, a payout or a win condition was tuned.'),
            self::Components => __('A card, board, token or piece of the physical game.'),
            self::PlayerInteraction => __('How players affect, block or trade with each other.'),
            self::Pacing => __('Turn length, downtime or the shape of the arc.'),
            self::Ux => __('Making something easier to see, reach or understand.'),
            self::Theme => __('The setting, the fiction or how the two fit the systems.'),
            self::Economy => __('The flow of resources through the game.'),
            self::Other => __('Anything that does not fit the categories above.'),
        };
    }
}
