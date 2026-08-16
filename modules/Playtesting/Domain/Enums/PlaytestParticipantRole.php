<?php

namespace Modules\Playtesting\Domain\Enums;

/**
 * What somebody was doing at a playtest session.
 *
 * The role is about the sitting, not about the platform. A workspace admin who
 * sat down and played is a player at that table, and somebody with no Barkeep
 * account at all can be the facilitator. Nothing here grants any permission —
 * see the policies for that.
 *
 * Kept small on purpose. "Blind playtester", "solo tester" and the rest of the
 * vocabulary designers use are distinctions the framework system will
 * eventually own; inventing them here would fix them before anybody has asked
 * for them.
 */
enum PlaytestParticipantRole: string
{
    case Player = 'player';
    case Observer = 'observer';
    case Facilitator = 'facilitator';
    case Designer = 'designer';

    /**
     * The role somebody is assumed to have unless they are given another.
     *
     * Most people at a playtest are playing, and asking for the role before
     * asking for the name would get in the way of the one thing the active
     * session screen has to be good at: adding somebody quickly.
     */
    public static function default(): self
    {
        return self::Player;
    }

    /**
     * Determine whether this role was actually playing the game.
     *
     * The distinction matters for reading a session back: "four players" and
     * "six people in the room" are different facts about the same evening.
     */
    public function isPlaying(): bool
    {
        return $this === self::Player;
    }

    /**
     * A human readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Player => __('Player'),
            self::Observer => __('Observer'),
            self::Facilitator => __('Facilitator'),
            self::Designer => __('Designer'),
        };
    }

    /**
     * What somebody in this role was there to do.
     */
    public function description(): string
    {
        return match ($this) {
            self::Player => __('Played the game.'),
            self::Observer => __('Watched without playing.'),
            self::Facilitator => __('Ran the session and taught the rules.'),
            self::Designer => __('Designed the game being tested.'),
        };
    }
}
