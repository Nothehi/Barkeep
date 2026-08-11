<?php

use App\Providers\AppServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;
use Modules\Workspace\Providers\WorkspaceServiceProvider;

return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    WorkspaceServiceProvider::class,
];
