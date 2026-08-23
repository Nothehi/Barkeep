<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Modules\GameRules\Domain\Models\GameEndCondition;

/**
 * The representation of something that brings the game to a close.
 *
 * Named rather than reimplemented. The payload is {@see OutcomeResource}'s,
 * because the three kinds of outcome collect identical fields; the separate class
 * exists so a controller reads as what it returns, and so the three can diverge
 * later without a migration of call sites.
 *
 * @mixin GameEndCondition
 */
class GameEndConditionResource extends OutcomeResource {}
