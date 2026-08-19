<?php

use App\Providers\AppServiceProvider;
use Modules\DesignFramework\Providers\DesignFrameworkServiceProvider;
use Modules\GameDesign\Providers\GameDesignServiceProvider;
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
 * PrototypeIteration is registered last because it is the only module that
 * reads two others: GameDesign, for the design versions a prototype and an
 * iteration are built against, and Playtesting, for the evidence a cycle was
 * judged on. Both of those bindings have to exist before its own resolve
 * through them. It does not depend on DesignFramework and DesignFramework does
 * not depend on it; they are siblings describing method and recording work.
 */
return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    WorkspaceServiceProvider::class,
    GameDesignServiceProvider::class,
    PlaytestingServiceProvider::class,
    DesignFrameworkServiceProvider::class,
    PrototypeIterationServiceProvider::class,
];
