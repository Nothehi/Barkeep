<?php

use App\Providers\AppServiceProvider;
use Modules\GameDesign\Providers\GameDesignServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;
use Modules\Playtesting\Providers\PlaytestingServiceProvider;
use Modules\Workspace\Providers\WorkspaceServiceProvider;

/*
 * Registered in dependency order: Identity owns accounts, Workspace owns the
 * tenancy boundary built on them, GameDesign owns the games inside that
 * boundary, and Playtesting owns the evidence gathered about their versions.
 * Each may reach down this list; none may reach up it.
 *
 * The order is load-bearing for more than tidiness. Playtesting's route
 * bindings resolve a playtest *through* the game the router has already
 * bound, so GameDesign's binding has to be registered first.
 */
return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    WorkspaceServiceProvider::class,
    GameDesignServiceProvider::class,
    PlaytestingServiceProvider::class,
];
