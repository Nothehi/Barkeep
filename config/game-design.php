<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mechanic curators
    |--------------------------------------------------------------------------
    |
    | The mechanics vocabulary is platform-wide. Every studio picks from one
    | list, which is the whole point of it: two games that both use worker
    | placement have to say so with the same word, or nothing can ever be
    | compared, filtered or reasoned about across them.
    |
    | That makes editing the list a platform concern rather than a workspace
    | one, and there is no workspace role that should imply it — a studio owner
    | curates their own games, not Barkeep's shared vocabulary. Reusing
    | `WorkspaceRole::Owner` here would be the easy thing and would hand every
    | signed-up account the ability to rename a term that appears on other
    | people's games.
    |
    | The bounded context that will own this properly is Administration, and it
    | does not exist yet. Until it does, the accounts allowed to curate the
    | vocabulary are named here by email address, exactly as framework
    | administrators are in `config/design-framework.php`. The two lists are
    | kept separate on purpose: writing a methodology and maintaining a
    | taxonomy are different jobs, and one installation may well want different
    | people doing them.
    |
    | An empty list means nobody may add or edit a mechanic. That is the
    | intended default: reading the vocabulary and tagging your own game with it
    | is open to every signed in account, and changing what the words mean
    | should require somebody having deliberately said who may.
    |
    | Set MECHANIC_CURATORS to a comma separated list of addresses.
    |
    */

    'curators' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MECHANIC_CURATORS', '')),
    ))),

];
