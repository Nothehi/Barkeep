<?php

/**
 * Playtesting sits at the bottom of the platform's dependency stack today, and
 * it is the module everything built next will want to read: gamification wants
 * to know a playtest happened, analytics wants to count them, notifications
 * want to announce them.
 *
 * These tests exist to make sure that wanting stays one-directional.
 * Playtesting reaches down into GameDesign, Workspace and Identity, and
 * everything above it subscribes to its events rather than being imported.
 */
$laterContexts = [
    'Modules\Administration',
    'Modules\Analytics',
    'Modules\Community',
    'Modules\Content',
    'Modules\Framework',
    'Modules\Gamification',
    'Modules\Knowledge',
    'Modules\Moderation',
];

arch('playtesting does not depend on any context built on top of it')
    ->expect('Modules\Playtesting')
    ->not->toUse($laterContexts);

/**
 * Playtesting may talk to Identity, Workspace and GameDesign, but only through
 * the parts they publish: their domain models and value objects, their
 * application queries and services, and their resources for rendering.
 * Reaching into any of their infrastructure or controllers would couple the
 * modules through their plumbing rather than through their language.
 */
arch('playtesting does not reach into another context\'s internals')
    ->expect('Modules\Playtesting')
    ->not->toUse([
        'Modules\Identity\Infrastructure',
        'Modules\Identity\Presentation\Http\Controllers',
        'Modules\Identity\Presentation\Http\Requests',
        'Modules\Workspace\Infrastructure',
        'Modules\Workspace\Presentation\Http\Controllers',
        'Modules\Workspace\Presentation\Http\Requests',
        'Modules\GameDesign\Infrastructure',
        'Modules\GameDesign\Presentation\Http\Controllers',
        'Modules\GameDesign\Presentation\Http\Requests',
    ]);

/**
 * The tenancy rules have one home, and it is not this one.
 *
 * "May this person work on this game?" already accounts for workspace
 * membership, the workspace's status and the game's own — GameDesign's policy
 * works all of that out, and Playtesting asking the same questions again would
 * be a second implementation that could disagree with the first.
 *
 * So a workspace role is read in exactly one file, and only ever to answer "is
 * this account on the team at all" before linking it to a participant. Nothing
 * else in the module knows what a role is.
 */
arch('only the workspace adapter knows what a workspace role is')
    ->expect([
        'Modules\Workspace\Domain\Enums\WorkspaceRole',
        'Modules\Workspace\Domain\Models\WorkspaceMember',
    ])
    ->not->toBeUsedIn([
        'Modules\Playtesting\Domain',
        'Modules\Playtesting\Application',
        'Modules\Playtesting\Presentation',
        'Modules\Playtesting\Providers',
        'Modules\Playtesting\Infrastructure\Authorization',
        'Modules\Playtesting\Infrastructure\GameDesign',
        'Modules\Playtesting\Infrastructure\Persistence',
    ]);

/**
 * The same arrangement one level up: reading a game's iterations happens in one
 * adapter, so the game/version invariant has a single enforcement point rather
 * than being re-derived wherever somebody needs a version.
 */
arch('only the game adapter reaches for a game\'s versions')
    ->expect('Modules\GameDesign\Application\Queries')
    ->not->toBeUsedIn([
        'Modules\Playtesting\Domain',
        'Modules\Playtesting\Presentation',
        'Modules\Playtesting\Infrastructure\Persistence',
    ]);

arch('the domain layer stays free of the layers above it')
    ->expect('Modules\Playtesting\Domain')
    ->not->toUse([
        'Modules\Playtesting\Application',
        'Modules\Playtesting\Presentation',
        'Modules\Playtesting\Providers',
    ]);

/**
 * The models are Eloquent active records and the policies answer the framework
 * gate, so both knowingly touch Illuminate. The rest of the domain has no such
 * excuse: it is where the rules live, and rules should be testable without a
 * database or a request.
 */
arch('the rest of the domain is free of the framework and of infrastructure')
    ->expect([
        'Modules\Playtesting\Domain\Enums',
        'Modules\Playtesting\Domain\Events',
        'Modules\Playtesting\Domain\Exceptions',
        'Modules\Playtesting\Domain\ValueObjects',
    ])
    ->not->toUse([
        'Modules\Playtesting\Infrastructure',
        'Illuminate\Database',
        'Illuminate\Http',
        'Inertia\Inertia',
    ]);

arch('the application layer does not depend on delivery concerns')
    ->expect('Modules\Playtesting\Application')
    ->not->toUse([
        'Modules\Playtesting\Presentation',
        'Inertia\Inertia',
    ]);

/**
 * The events are the module's published surface for everything built next, so
 * a consumer must never be handed something it can quietly mutate.
 */
arch('domain events are immutable value objects')
    ->expect('Modules\Playtesting\Domain\Events')
    ->toBeFinal()
    ->toBeReadonly();

arch('value objects carry no persistence concerns')
    ->expect('Modules\Playtesting\Domain\ValueObjects')
    ->not->toUse('Illuminate\Database');

arch('application commands, queries and services are final')
    ->expect([
        'Modules\Playtesting\Application\Commands',
        'Modules\Playtesting\Application\Queries',
        'Modules\Playtesting\Application\DTOs',
        'Modules\Playtesting\Application\Services',
    ])
    ->classes()
    ->toBeFinal();

/**
 * Nothing outside the module gets to touch a playtesting table directly.
 * Later contexts react to events; the moment Gamification writes its own
 * `Playtest::where(...)`, the ownership-chain scoping has two homes.
 */
arch('nothing outside playtesting reaches for its models or repositories')
    ->expect([
        'Modules\Playtesting\Domain\Models',
        'Modules\Playtesting\Infrastructure\Persistence',
    ])
    ->not->toBeUsedIn([
        'Modules\Identity',
        'Modules\Workspace',
        'Modules\GameDesign',
        'App\Http\Middleware',
    ]);

/**
 * The inverse of the first test, checked from the other side. The three
 * modules Playtesting is built on must not have learned that it exists — which
 * is what keeps them buildable, testable and extractable without it.
 */
arch('the contexts below have not learned about playtesting')
    ->expect([
        'Modules\Identity',
        'Modules\Workspace',
        'Modules\GameDesign',
    ])
    ->not->toUse('Modules\Playtesting');
