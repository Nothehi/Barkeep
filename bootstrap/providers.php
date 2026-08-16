<?php

use App\Providers\AppServiceProvider;
use Modules\GameDesign\Providers\GameDesignServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;
use Modules\Workspace\Providers\WorkspaceServiceProvider;

/*
 * Registered in dependency order: Identity owns accounts, Workspace owns the
 * tenancy boundary built on them, and GameDesign owns the games inside that
 * boundary. Each may reach down this list; none may reach up it.
 */
return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    WorkspaceServiceProvider::class,
    GameDesignServiceProvider::class,
];
