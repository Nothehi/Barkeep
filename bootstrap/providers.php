<?php

use App\Providers\AppServiceProvider;
use Modules\DesignFramework\Providers\DesignFrameworkServiceProvider;
use Modules\GameDesign\Providers\GameDesignServiceProvider;
use Modules\GameEconomy\Providers\GameEconomyServiceProvider;
use Modules\GameRules\Providers\GameRulesServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;
use Modules\Playtesting\Providers\PlaytestingServiceProvider;
use Modules\PrototypeIteration\Providers\PrototypeIterationServiceProvider;
use Modules\Workspace\Providers\WorkspaceServiceProvider;

/*
 * Registered in dependency order: Identity owns accounts, Workspace owns the
 * tenancy boundary built on them, GameDesign owns the games inside that
 * boundary, Playtesting owns the evidence gathered about their versions,
 * DesignFramework owns the methodology a game can choose to follow, and
 * PrototypeIteration owns the record of the design work itself. Each may
 * reach down this list; none may reach up it.
 *
 * The order is load-bearing for more than tidiness. Playtesting's route
 * bindings resolve a playtest *through* the game the router has already
 * bound, so GameDesign's binding has to be registered first — and
 * DesignFramework's content bindings resolve through the same game, so it
 * comes after GameDesign too.
 *
 * DesignFramework sits below Playtesting in this list and does not depend on
 * it. The two are siblings: Playtesting produces evidence, DesignFramework
 * describes method, and neither imports the other. Architecture tests on both
 * sides hold that line.
 *
 * GameEconomy is registered last. It reads GameDesign — every balance profile
 * belongs to a design version — and its route bindings resolve *through* the
 * `{version}` binding GameDesign declares, so that binding has to exist before
 * its own. It deliberately does not claim `{version}` itself; see
 * `.ai/rules/providers.md` for what happens when two providers bind one name.
 *
 * It is a sibling of Playtesting, DesignFramework and PrototypeIteration rather
 * than a dependant of any of them: it models the quantitative systems that need
 * balancing, where those record evidence, method and work. None of the four
 * imports another.
 *
 * PrototypeIteration is registered before it because it is the only module that
 * reads two others: GameDesign, for the design versions a prototype and an
 * iteration are built against, and Playtesting, for the evidence a cycle was
 * judged on. Both of those bindings have to exist before its own resolve
 * through them. It does not depend on DesignFramework and DesignFramework does
 * not depend on it; they are siblings describing method and recording work.
 *
 * GameRules is registered last, after GameEconomy, and both halves of that
 * matter. It reads GameDesign — every rule set belongs to a design version — so
 * its route bindings resolve through the `{version}` binding GameDesign
 * declares. And it reads GameEconomy, through exactly one adapter, so that a
 * rule action can show what it costs without holding a copy of the number: the
 * economy has to be registered before the module that reads it.
 *
 * The direction is one-way and stays that way. GameEconomy does not know
 * GameRules exists, and an architecture test on each side holds the line. Seven
 * of this module's route parameters are longer than they would naturally be —
 * `{gameRule}`, `{gamePhase}`, `{ruleMechanic}`, `{ruleAction}`, `{ruleEffect}`,
 * `{ruleCondition}` — because the short forms were already claimed by the
 * modules above it in this list; see `.ai/rules/providers.md`.
 */
return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    WorkspaceServiceProvider::class,
    GameDesignServiceProvider::class,
    PlaytestingServiceProvider::class,
    DesignFrameworkServiceProvider::class,
    PrototypeIterationServiceProvider::class,
    GameEconomyServiceProvider::class,
    GameRulesServiceProvider::class,
];
