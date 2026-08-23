<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Modules\GameRules\Domain\Models\VictoryCondition;

/**
 * The representation of a way to win the game.
 *
 * Named rather than reimplemented. The payload is {@see OutcomeResource}'s,
 * because the three kinds of outcome collect identical fields; the separate class
 * exists so a controller reads as what it returns, and so the three can diverge
 * later without a migration of call sites.
 *
 * @mixin VictoryCondition
 */
class VictoryConditionResource extends OutcomeResource {}
