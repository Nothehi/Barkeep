<?php

namespace Modules\GameDesign\Domain\Enums;

/**
 * How far a game has got in the design process.
 *
 * Deliberately a fixed list rather than a configurable workflow. Board-game
 * design does follow a broadly agreed arc, and pinning it down now is what
 * lets the rest of the platform say useful things about a project. When the
 * design framework arrives and teams want their own phases, this enum becomes
 * the default set rather than the only one.
 *
 * Phases are ordered but not policed: designers loop back to prototyping from
 * playtesting all the time, and refusing that would describe a process nobody
 * follows. The order exists so progress can be *shown*, not enforced.
 *
 * Independent of {@see GameStatus}, which is about the project rather than
 * the design: `Active` + `Prototyping` and `OnHold` + `Playtesting` are both
 * ordinary states.
 */
enum DesignPhase: string
{
    case Idea = 'idea';
    case Concept = 'concept';
    case CoreDesign = 'core_design';
    case Prototyping = 'prototyping';
    case Playtesting = 'playtesting';
    case Development = 'development';
    case Production = 'production';
    case Published = 'published';

    /**
     * The phase a game starts life in.
     */
    public static function default(): self
    {
        return self::Idea;
    }

    /**
     * The phase's place in the arc, counting from one.
     */
    public function position(): int
    {
        return match ($this) {
            self::Idea => 1,
            self::Concept => 2,
            self::CoreDesign => 3,
            self::Prototyping => 4,
            self::Playtesting => 5,
            self::Development => 6,
            self::Production => 7,
            self::Published => 8,
        };
    }

    /**
     * How many phases there are in total.
     */
    public static function count(): int
    {
        return count(self::cases());
    }

    /**
     * A human readable label for the phase.
     */
    public function label(): string
    {
        return match ($this) {
            self::Idea => __('Idea'),
            self::Concept => __('Concept'),
            self::CoreDesign => __('Core design'),
            self::Prototyping => __('Prototyping'),
            self::Playtesting => __('Playtesting'),
            self::Development => __('Development'),
            self::Production => __('Production'),
            self::Published => __('Published'),
        };
    }

    /**
     * A short description of what the phase involves.
     *
     * Shown next to the phase when somebody is choosing one, so the list
     * means something to a designer who has not read a framework yet.
     */
    public function description(): string
    {
        return match ($this) {
            self::Idea => __('A spark worth writing down.'),
            self::Concept => __('The pitch, the fantasy and the shape of a turn.'),
            self::CoreDesign => __('The core loop, the rules and how the game ends.'),
            self::Prototyping => __('A playable version, however ugly.'),
            self::Playtesting => __('Real people, real tables, real problems.'),
            self::Development => __('Balancing, tightening and cutting.'),
            self::Production => __('Components, art, rulebook and manufacturing.'),
            self::Published => __('Out in the world.'),
        };
    }
}
