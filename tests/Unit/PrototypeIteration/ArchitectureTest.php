<?php

/**
 * PrototypeIteration is the only module in the platform that reads two others: GameDesign, for the design
 * versions a prototype and a cycle are built against, and Playtesting, for the evidence a cycle was judged
 * on. It is also the module everything built next will want to read — analytics wants to count iterations,
 * gamification wants to know a decision was accepted, AI wants to suggest changes.
 *
 * These tests exist to keep both of those relationships one-directional, and to keep each of the two
 * dependencies confined to the single adapter that speaks for it.
 */
$laterContexts = [
    'Modules\Administration',
    'Modules\Analytics',
    'Modules\Community',
    'Modules\Content',
    'Modules\Gamification',
    'Modules\Knowledge',
    'Modules\Moderation',
];

arch('prototype iteration does not depend on any context built on top of it')
    ->expect('Modules\PrototypeIteration')
    ->not->toUse($laterContexts);

/**
 * DesignFramework is a sibling rather than a dependency. It describes what good design work should look
 * like; this module records the work that was actually done. Neither imports the other, and the framework
 * side holds the same line from its own test.
 */
arch('prototype iteration and design framework do not know about each other')
    ->expect('Modules\PrototypeIteration')
    ->not->toUse('Modules\DesignFramework');

/**
 * The module may talk to Identity, Workspace, GameDesign and Playtesting, but only through the parts they
 * publish: their domain models and value objects, their application commands, queries and services, and
 * their resources for rendering. Reaching into any of their infrastructure or controllers would couple the
 * modules through their plumbing rather than through their language.
 */
arch('prototype iteration does not reach into another context\'s internals')
    ->expect('Modules\PrototypeIteration')
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
        'Modules\Playtesting\Infrastructure',
        'Modules\Playtesting\Presentation',
    ]);

/**
 * The tenancy rules have one home, and it is not this one.
 *
 * "May this person work on this game?" already accounts for workspace membership, the workspace's status and
 * the game's own — GameDesign's policy works all of that out, and asking the same questions again here would
 * be a second implementation that could disagree with the first. So nothing in this module knows what a
 * workspace role is.
 */
arch('nothing in prototype iteration knows what a workspace role is')
    ->expect([
        'Modules\Workspace\Domain\Enums\WorkspaceRole',
        'Modules\Workspace\Domain\Models\WorkspaceMember',
    ])
    ->not->toBeUsedIn('Modules\PrototypeIteration');

/**
 * Reading a game's design versions happens in one adapter, so the game/version half of the central invariant
 * has a single enforcement point rather than being re-derived wherever somebody needs a version — and so
 * that cutting a version from an iteration cannot bypass GameDesign's own allocator.
 */
arch('only the game adapter reaches for GameDesign\'s commands and queries')
    ->expect([
        'Modules\GameDesign\Application\Commands',
        'Modules\GameDesign\Application\Queries',
    ])
    ->not->toBeUsedIn([
        'Modules\PrototypeIteration\Domain',
        'Modules\PrototypeIteration\Presentation',
        'Modules\PrototypeIteration\Infrastructure\Persistence',
        'Modules\PrototypeIteration\Infrastructure\Playtesting',
        'Modules\PrototypeIteration\Infrastructure\Storage',
        'Modules\PrototypeIteration\Infrastructure\Authorization',
    ]);

/**
 * The seam this module is most careful about, and section 28 in one assertion.
 *
 * Exactly one file knows that Playtesting exists. Everything else works with this module's own
 * `PlaytestReference` and `CitedEvidence` value objects, so no observation, piece of feedback or participant
 * is ever copied here and no Playtesting model reaches a command, a controller or a resource.
 *
 * The moment a second file imports a Playtesting class, the boundary has stopped being a boundary — a
 * convenient `with('playtest')` in a repository is exactly how that happens.
 */
arch('only the playtesting adapter knows that Playtesting exists')
    ->expect('Modules\Playtesting')
    ->not->toBeUsedIn([
        'Modules\PrototypeIteration\Domain',
        'Modules\PrototypeIteration\Application',
        'Modules\PrototypeIteration\Presentation',
        'Modules\PrototypeIteration\Providers',
        'Modules\PrototypeIteration\Infrastructure\Persistence',
        'Modules\PrototypeIteration\Infrastructure\Storage',
        'Modules\PrototypeIteration\Infrastructure\GameDesign',
        'Modules\PrototypeIteration\Infrastructure\Authorization',
    ]);

/**
 * The disk has one home too. No controller opens a stream, builds a path by concatenation or decides what a
 * URL should look like — which is what keeps "artifacts are private and served through the policy" true
 * rather than customary.
 */
arch('only the storage adapter touches a filesystem')
    ->expect(['Illuminate\Support\Facades\Storage', 'Illuminate\Filesystem'])
    ->not->toBeUsedIn([
        'Modules\PrototypeIteration\Domain',
        'Modules\PrototypeIteration\Application',
        'Modules\PrototypeIteration\Presentation',
        'Modules\PrototypeIteration\Infrastructure\Persistence',
    ]);

arch('the domain layer stays free of the layers above it')
    ->expect('Modules\PrototypeIteration\Domain')
    ->not->toUse([
        'Modules\PrototypeIteration\Application',
        'Modules\PrototypeIteration\Presentation',
        'Modules\PrototypeIteration\Providers',
    ]);

/**
 * The models are Eloquent active records and the policies answer the framework gate, so both knowingly touch
 * Illuminate. The rest of the domain has no such excuse: it is where the rules live, and rules should be
 * testable without a database or a request.
 */
arch('the rest of the domain is free of the framework and of infrastructure')
    ->expect([
        'Modules\PrototypeIteration\Domain\Enums',
        'Modules\PrototypeIteration\Domain\Events',
        'Modules\PrototypeIteration\Domain\Exceptions',
        'Modules\PrototypeIteration\Domain\ValueObjects',
    ])
    ->not->toUse([
        'Modules\PrototypeIteration\Infrastructure',
        'Illuminate\Database',
        'Illuminate\Http',
        'Inertia\Inertia',
    ]);

arch('the application layer does not depend on delivery concerns')
    ->expect('Modules\PrototypeIteration\Application')
    ->not->toUse([
        'Modules\PrototypeIteration\Presentation',
        'Inertia\Inertia',
    ]);

/**
 * The events are the module's published surface for everything built next, so a consumer must never be handed
 * something it can quietly mutate.
 */
arch('domain events are immutable value objects')
    ->expect('Modules\PrototypeIteration\Domain\Events')
    ->toBeFinal()
    ->toBeReadonly();

arch('value objects carry no persistence concerns')
    ->expect('Modules\PrototypeIteration\Domain\ValueObjects')
    ->not->toUse('Illuminate\Database');

arch('application commands, queries, DTOs and services are final')
    ->expect([
        'Modules\PrototypeIteration\Application\Commands',
        'Modules\PrototypeIteration\Application\Queries',
        'Modules\PrototypeIteration\Application\DTOs',
        'Modules\PrototypeIteration\Application\Services',
    ])
    ->classes()
    ->toBeFinal();

arch('the adapters that speak for another context are final')
    ->expect([
        'Modules\PrototypeIteration\Infrastructure\GameDesign',
        'Modules\PrototypeIteration\Infrastructure\Playtesting',
        'Modules\PrototypeIteration\Infrastructure\Storage',
        'Modules\PrototypeIteration\Infrastructure\Authorization',
        'Modules\PrototypeIteration\Infrastructure\Persistence\Repositories',
    ])
    ->classes()
    ->toBeFinal();

/**
 * Nothing outside the module gets to touch a prototype or iteration table directly. Later contexts react to
 * events; the moment Analytics writes its own `Iteration::where(...)`, the ownership-chain scoping has two
 * homes.
 */
arch('nothing outside prototype iteration reaches for its models or repositories')
    ->expect([
        'Modules\PrototypeIteration\Domain\Models',
        'Modules\PrototypeIteration\Infrastructure\Persistence',
    ])
    ->not->toBeUsedIn([
        'Modules\Identity',
        'Modules\Workspace',
        'Modules\GameDesign',
        'Modules\Playtesting',
        'Modules\DesignFramework',
        'App\Http\Middleware',
    ]);

/**
 * The inverse of the first test, checked from the other side. The five modules PrototypeIteration sits above
 * must not have learned that it exists — which is what keeps them buildable, testable and extractable
 * without it.
 */
arch('the contexts below have not learned about prototype iteration')
    ->expect([
        'Modules\Identity',
        'Modules\Workspace',
        'Modules\GameDesign',
        'Modules\Playtesting',
        'Modules\DesignFramework',
    ])
    ->not->toUse('Modules\PrototypeIteration');
