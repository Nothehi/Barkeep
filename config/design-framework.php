<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Framework administrators
    |--------------------------------------------------------------------------
    |
    | Frameworks are platform-wide: one methodology, adopted by studios across
    | every workspace. Editing one is therefore not a workspace permission, and
    | there is no workspace role that should imply it — a studio owner
    | administers their own studio, not Barkeep's design methodology.
    |
    | The bounded context that will own this properly is Administration, and it
    | does not exist yet. Until it does, the accounts allowed to write
    | frameworks are named here by email address. It is a deliberately small,
    | obvious seam: `FrameworkAdministrators` is the only thing that reads this
    | list, `FrameworkPolicy` is the only thing that asks it, and when
    | Administration arrives it replaces the adapter without any policy,
    | controller or screen changing.
    |
    | An empty list means nobody may create or edit a framework. That is the
    | intended default for a fresh install: reading published frameworks is open
    | to every signed in account, and writing them should require somebody
    | having deliberately said who may.
    |
    | Set FRAMEWORK_ADMINISTRATORS to a comma separated list of addresses.
    |
    */

    'administrators' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FRAMEWORK_ADMINISTRATORS', '')),
    ))),

];
