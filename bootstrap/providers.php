<?php

use App\Providers\AppServiceProvider;
use Modules\DesignFramework\Providers\DesignFrameworkServiceProvider;
use Modules\GameDesign\Providers\GameDesignServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;
use Modules\Playtesting\Providers\PlaytestingServiceProvider;
use Modules\Workspace\Providers\WorkspaceServiceProvider;

/*
 * Registered in dependency order: Identity owns accounts, Workspace owns the
 * tenancy boundary built on them, GameDesign owns the games inside that
 * boundary, Playtesting owns the evidence gathered about their versions, and
 * DesignFramework owns the methodology a game can choose to follow. Each may
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
 */
return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    WorkspaceServiceProvider::class,
    GameDesignServiceProvider::class,
    PlaytestingServiceProvider::class,
    DesignFrameworkServiceProvider::class,
];
