<?php

/**
 * GameDesign owns the product's core aggregate, and everything else the
 * platform will grow is going to want a piece of it. These tests exist to
 * make sure that wanting stays one-directional: GameDesign reaches down into
 * Workspace and Identity, and everything above it subscribes to events rather
 * than being imported.
 */
$laterContexts = [
    'Modules\Administration',
    'Modules\Analytics',
    'Modules\Community',
    'Modules\Content',
    'Modules\Framework',
    'Modules\Gamification',
    'Modules\Moderation',
    'Modules\Playtesting',
];

/**
 * The rule that keeps Playtesting buildable. It will consume games and
 * versions; GameDesign must never learn that it exists, or the two become one
 * module with two folders.
 */
arch('game design does not depend on any context built on top of it')
    ->expect('Modules\GameDesign')
    ->not->toUse($laterContexts);

/**
 * GameDesign may talk to Identity and Workspace, but only through the parts
 * they publish: their domain models and value objects, their application
 * queries, and their resources for rendering. Reaching into either one's
 * infrastructure or controllers would couple the modules through their
 * plumbing rather than through their language.
 */
arch('game design does not reach into another context\'s internals')
    ->expect('Modules\GameDesign')
    ->not->toUse([
        'Modules\Identity\Infrastructure',
        'Modules\Identity\Presentation\Http\Controllers',
        'Modules\Identity\Presentation\Http\Requests',
        'Modules\Workspace\Infrastructure',
        'Modules\Workspace\Presentation\Http\Controllers',
        'Modules\Workspace\Presentation\Http\Requests',
    ]);

/**
 * Workspace's roles are Workspace's business.
 *
 * GameDesign needs to know what a workspace permits, but translating a role
 * into that answer happens in exactly one adapter. Everywhere else in the
 * module speaks in terms of a `WorkspaceGrant` instead, so when Workspace
 * grows a fourth role there is one file to change rather than a policy, five
 * commands and a handful of controllers.
 */
arch('only the workspace adapter knows what a workspace role is')
    ->expect([
        'Modules\Workspace\Domain\Enums\WorkspaceRole',
        'Modules\Workspace\Domain\Models\WorkspaceMember',
    ])
    ->not->toBeUsedIn([
        'Modules\GameDesign\Domain',
        'Modules\GameDesign\Application',
        'Modules\GameDesign\Presentation',
        'Modules\GameDesign\Providers',
        'Modules\GameDesign\Infrastructure\Persistence',
        'Modules\GameDesign\Infrastructure\Search',
    ]);

arch('the domain layer stays free of the layers above it')
    ->expect('Modules\GameDesign\Domain')
    ->not->toUse([
        'Modules\GameDesign\Application',
        'Modules\GameDesign\Presentation',
        'Modules\GameDesign\Providers',
    ]);

/**
 * The models are Eloquent active records and the policy answers the framework
 * gate, so both knowingly touch Illuminate. The rest of the domain has no
 * such excuse: it is where the rules live, and rules should be testable
 * without a database or a request.
 */
arch('the rest of the domain is free of the framework and of infrastructure')
    ->expect([
        'Modules\GameDesign\Domain\Enums',
        'Modules\GameDesign\Domain\Events',
        'Modules\GameDesign\Domain\Exceptions',
        'Modules\GameDesign\Domain\ValueObjects',
    ])
    ->not->toUse([
        'Modules\GameDesign\Infrastructure',
        'Illuminate\Database',
        'Illuminate\Http',
        'Inertia\Inertia',
    ]);

arch('the application layer does not depend on delivery concerns')
    ->expect('Modules\GameDesign\Application')
    ->not->toUse([
        'Modules\GameDesign\Presentation',
        'Inertia\Inertia',
    ]);

arch('domain events are immutable value objects')
    ->expect('Modules\GameDesign\Domain\Events')
    ->toBeFinal()
    ->toBeReadonly();

arch('value objects carry no persistence concerns')
    ->expect('Modules\GameDesign\Domain\ValueObjects')
    ->not->toUse('Illuminate\Database');

arch('application commands, queries and services are final')
    ->expect('Modules\GameDesign\Application')
    ->classes()
    ->toBeFinal();

/**
 * Nothing outside the module gets to touch a game table directly. Other
 * contexts go through GameDesign's own queries, which is what keeps the
 * workspace scoping in one place — the moment Playtesting writes its own
 * `Game::where(...)`, the scoping rule has two homes.
 */
arch('nothing outside game design reaches for its models or repositories')
    ->expect([
        'Modules\GameDesign\Domain\Models',
        'Modules\GameDesign\Infrastructure\Persistence',
    ])
    ->not->toBeUsedIn([
        'Modules\Identity',
        'Modules\Workspace',
        'App\Http\Middleware',
    ]);

/**
 * The inverse of the first test, checked from the other side. Workspace must
 * not have grown a reference to games — which is exactly what would have
 * happened had the game route binding been left to Laravel's scoped implicit
 * bindings, since those resolve a child through a relation on the parent.
 */
arch('workspace has not learned about games')
    ->expect('Modules\Workspace')
    ->not->toUse('Modules\GameDesign');

arch('identity has not learned about games')
    ->expect('Modules\Identity')
    ->not->toUse('Modules\GameDesign');
