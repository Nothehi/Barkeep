<?php

/**
 * Workspace establishes the tenancy boundary every later context builds on,
 * so its dependencies have to point one way: down into Identity, and nowhere
 * sideways. These tests fail the moment that stops being true.
 */
$laterContexts = [
    'Modules\Administration',
    'Modules\Analytics',
    'Modules\Community',
    'Modules\Content',
    'Modules\DesignFramework',
    'Modules\GameDesign',
    'Modules\Gamification',
    'Modules\Moderation',
    'Modules\Playtesting',
    'Modules\PrototypeIteration',
];

arch('workspace does not depend on any context built on top of it')
    ->expect('Modules\Workspace')
    ->not->toUse($laterContexts);

/**
 * Workspace may talk to Identity, but only through the parts Identity
 * publishes: its application queries, its domain model and value objects, and
 * its resource for rendering an account. Reaching into Identity's
 * infrastructure would couple the two through their storage rather than
 * through their language.
 */
arch('workspace does not reach into identity internals')
    ->expect('Modules\Workspace')
    ->not->toUse([
        'Modules\Identity\Infrastructure',
        'Modules\Identity\Presentation\Http\Controllers',
        'Modules\Identity\Presentation\Http\Requests',
    ]);

arch('the domain layer stays free of the layers above it')
    ->expect('Modules\Workspace\Domain')
    ->not->toUse([
        'Modules\Workspace\Application',
        'Modules\Workspace\Presentation',
        'Modules\Workspace\Providers',
    ]);

/**
 * The models are Eloquent active records and the policy answers the framework
 * gate, so both knowingly touch Illuminate. The rest of the domain has no
 * such excuse: it is where the rules live, and rules should be testable
 * without a database or a request.
 */
arch('the rest of the domain is free of the framework and of infrastructure')
    ->expect([
        'Modules\Workspace\Domain\Enums',
        'Modules\Workspace\Domain\Events',
        'Modules\Workspace\Domain\Exceptions',
        'Modules\Workspace\Domain\ValueObjects',
    ])
    ->not->toUse([
        'Modules\Workspace\Infrastructure',
        'Illuminate\Database',
        'Illuminate\Http',
        'Inertia\Inertia',
    ]);

arch('the application layer does not depend on delivery concerns')
    ->expect('Modules\Workspace\Application')
    ->not->toUse([
        'Modules\Workspace\Presentation',
        'Inertia\Inertia',
    ]);

arch('domain events are immutable value objects')
    ->expect('Modules\Workspace\Domain\Events')
    ->toBeFinal()
    ->toBeReadonly();

arch('value objects carry no persistence concerns')
    ->expect('Modules\Workspace\Domain\ValueObjects')
    ->not->toUse('Illuminate\Database');

arch('application commands and queries are final')
    ->expect('Modules\Workspace\Application')
    ->classes()
    ->toBeFinal();

/**
 * Nothing outside the module gets to touch a workspace table directly. Other
 * contexts go through Workspace's own queries, which is what keeps the
 * membership scoping in one place.
 */
arch('nothing outside workspace reaches for its models or repositories')
    ->expect([
        'Modules\Workspace\Domain\Models',
        'Modules\Workspace\Infrastructure\Persistence',
    ])
    ->not->toBeUsedIn([
        'Modules\Identity',
        'App\Http\Middleware',
    ]);
