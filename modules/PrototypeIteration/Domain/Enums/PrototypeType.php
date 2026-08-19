<?php

namespace Modules\PrototypeIteration\Domain\Enums;

/**
 * What kind of thing the prototype physically is.
 *
 * The distinction that matters to a designer is how the prototype gets in
 * front of players, because that decides what an iteration costs. Reprinting a
 * sheet of cards is an afternoon; rebuilding a digital simulation is a week;
 * reprinting a 3D part is a week and a courier. Recording the kind is what
 * makes "we iterated four times" mean something different for each.
 *
 * Deliberately a fixed, short list rather than a plugin system. Five cases
 * cover every prototype a tabletop designer builds today, and {@see Other}
 * exists so nobody is forced to file a sixth kind under the wrong heading
 * while waiting for the taxonomy to grow.
 */
enum PrototypeType: string
{
    case Paper = 'paper';
    case Digital = 'digital';
    case Physical = 'physical';
    case Hybrid = 'hybrid';
    case Other = 'other';

    /**
     * The kind a prototype is assumed to be when nobody said.
     *
     * Paper, because that is what almost every prototype starts as — a printed
     * sheet of cards and a handful of borrowed cubes.
     */
    public static function default(): self
    {
        return self::Paper;
    }

    /**
     * A human readable label for the kind.
     */
    public function label(): string
    {
        return match ($this) {
            self::Paper => __('Paper'),
            self::Digital => __('Digital'),
            self::Physical => __('Physical'),
            self::Hybrid => __('Hybrid'),
            self::Other => __('Other'),
        };
    }

    /**
     * The kind of thing that belongs under this heading.
     *
     * Written as examples rather than as definitions, because somebody
     * choosing a kind recognises an example faster than they parse a category.
     */
    public function description(): string
    {
        return match ($this) {
            self::Paper => __('Printed cards, a hand-drawn board, borrowed cubes.'),
            self::Digital => __('A simulation, a spreadsheet model or a playable build.'),
            self::Physical => __('Moulded, laser-cut or 3D printed components.'),
            self::Hybrid => __('A physical game with a digital companion, or the reverse.'),
            self::Other => __('Anything that does not fit the kinds above.'),
        };
    }
}
