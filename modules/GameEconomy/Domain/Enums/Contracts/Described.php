<?php

namespace Modules\GameEconomy\Domain\Enums\Contracts;

/**
 * An enum whose cases explain what belongs under them.
 *
 * The distinction from {@see Labelled} is one a designer feels rather than a
 * programmer: a status is a word somebody already understands, where a category
 * is a choice they are being asked to make. The second needs a sentence beside
 * it in the picker, and this interface is how the option builder knows which
 * enums have one.
 */
interface Described extends Labelled
{
    /**
     * What belongs under this heading.
     */
    public function description(): string;
}
