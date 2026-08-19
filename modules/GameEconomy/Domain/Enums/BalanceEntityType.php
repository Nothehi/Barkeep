<?php

namespace Modules\GameEconomy\Domain\Enums;

use Modules\GameEconomy\Domain\Enums\Contracts\Labelled;

/**
 * What kind of thing a finding is about.
 *
 * A warning names the record it concerns so the interface can link to it, and
 * this is the half of that reference that says which list to look in. An enum
 * rather than a class string, because these travel to the client and a namespace
 * is neither useful nor safe to publish there.
 */
enum BalanceEntityType: string implements Labelled
{
    case Profile = 'profile';
    case Resource = 'resource';
    case Flow = 'flow';
    case Action = 'action';
    case Cost = 'cost';
    case Reward = 'reward';
    case Effect = 'effect';
    case Variable = 'variable';

    /**
     * A human readable label for the kind of record.
     */
    public function label(): string
    {
        return match ($this) {
            self::Profile => __('Balance profile'),
            self::Resource => __('Resource'),
            self::Flow => __('Resource flow'),
            self::Action => __('Action'),
            self::Cost => __('Cost'),
            self::Reward => __('Reward'),
            self::Effect => __('Effect'),
            self::Variable => __('Variable'),
        };
    }
}
