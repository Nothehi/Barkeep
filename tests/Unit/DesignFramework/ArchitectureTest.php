<?php

/**
 * DesignFramework sits above GameDesign and beside Playtesting, and the shape of that sentence is what
 * these tests protect.
 *
 * "Above GameDesign" means it may read games and versions and never owns them. "Beside Playtesting"
 * means neither imports the other, even though a practice called "run a two-player playtest" is
 * obviously about something Playtesting can see happen. When that integration arrives it belongs in
 * whichever context observes both, not in a dependency from one to the other.
 */
$laterContexts = [
    'Modules\Administration',
    'Modules\Analytics',
    'Modules\Community',
    'Modules\Content',
    'Modules\Gamification',
    'Modules\Knowledge',
    'Modules\Moderation',
    'Modules\PrototypeIteration',
];

arch('the design framework does not depend on any context built on top of it')
    ->expect('Modules\DesignFramework')
    ->not->toUse($laterContexts);

/**
 * The sibling rule, and the one most likely to be broken by somebody being helpful.
 *
 * Section 43 is explicit: Playtesting is not a dependency of DesignFramework. A practice is an
 * instruction a designer marks complete themselves, and closing that loop automatically is a future
 * integration rather than a coupling.
 */
arch('the design framework does not reach into playtesting')
    ->expect('Modules\DesignFramework')
    ->not->toUse('Modules\Playtesting');

arch('playtesting has not learned about the design framework either')
    ->expect('Modules\Playtesting')
    ->not->toUse('Modules\DesignFramework');

/**
 * DesignFramework may talk to Identity, Workspace and GameDesign, but only through the parts they
 * publish: their domain models and value objects, their application queries and services, and their
 * resources for rendering. Reaching into any of their infrastructure or controllers would couple the
 * modules through their plumbing rather than through their language.
 */
arch('the design framework does not reach into another context\'s internals')
    ->expect('Modules\DesignFramework')
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
 * "May this person work on this game?" already accounts for workspace membership, the workspace's
 * status and the game's own — GameDesign's policy works all of that out, and asking the same questions
 * again here would be a second implementation that could disagree with the first.
 *
 * So no workspace role is read anywhere in this module, and the game gate is asked in exactly one
 * adapter.
 */
arch('the design framework never reads a workspace role')
    ->expect([
        'Modules\Workspace\Domain\Enums\WorkspaceRole',
        'Modules\Workspace\Domain\Models\WorkspaceMember',
    ])
    ->not->toBeUsedIn('Modules\DesignFramework');

/**
 * The most important separation in the module, checked from the outside.
 *
 * GameDesign's `DesignPhase` is an enum on a game — "where is this game now?". This module's phase is a
 * row in a framework version — "what should a designer work on at this stage?". Section 7 says not to
 * reuse the enum, and the reason is that the two vocabularies belong to different things: a game in
 * GameDesign's `prototyping` phase may be working through this module's "Core loop".
 *
 * Conflating them would be an easy mistake to make and a very hard one to unpick, because every
 * framework version may name its stages differently.
 */
arch('the design framework does not reuse GameDesign\'s design phase enum')
    ->expect('Modules\GameDesign\Domain\Enums\DesignPhase')
    ->not->toBeUsedIn('Modules\DesignFramework');

arch('the domain layer stays free of the layers above it')
    ->expect('Modules\DesignFramework\Domain')
    ->not->toUse([
        'Modules\DesignFramework\Application',
        'Modules\DesignFramework\Presentation',
        'Modules\DesignFramework\Providers',
    ]);

/**
 * The models are Eloquent active records and the policies answer the framework gate, so both knowingly
 * touch Illuminate. The rest of the domain has no such excuse: it is where the rules live, and rules
 * should be testable without a database or a request.
 */
arch('the rest of the domain is free of the framework and of infrastructure')
    ->expect([
        'Modules\DesignFramework\Domain\Enums',
        'Modules\DesignFramework\Domain\Events',
        'Modules\DesignFramework\Domain\Exceptions',
        'Modules\DesignFramework\Domain\ValueObjects',
    ])
    ->not->toUse([
        'Modules\DesignFramework\Infrastructure',
        'Illuminate\Database',
        'Illuminate\Http',
        'Inertia\Inertia',
    ]);

arch('the application layer does not depend on delivery concerns')
    ->expect('Modules\DesignFramework\Application')
    ->not->toUse([
        'Modules\DesignFramework\Presentation',
        'Inertia\Inertia',
    ]);

/**
 * The events are the module's published surface for everything built next, so a consumer must never be
 * handed something it can quietly mutate.
 */
arch('domain events are immutable value objects')
    ->expect('Modules\DesignFramework\Domain\Events')
    ->toBeFinal()
    ->toBeReadonly();

arch('value objects carry no persistence concerns')
    ->expect('Modules\DesignFramework\Domain\ValueObjects')
    ->not->toUse('Illuminate\Database');

arch('application commands, queries, DTOs and services are final')
    ->expect([
        'Modules\DesignFramework\Application\Commands',
        'Modules\DesignFramework\Application\Queries',
        'Modules\DesignFramework\Application\DTOs',
        'Modules\DesignFramework\Application\Services',
    ])
    ->classes()
    ->toBeFinal();

/**
 * Ordering is the module's most repeated rule and its easiest to get subtly wrong, so it has exactly
 * one implementation.
 *
 * `Position` is the type that says what a position may be, and `ContentSequencer` is the only thing
 * that writes one. If a command starts assigning positions itself, one list somewhere ends up with two
 * items at position 3 and nobody notices until a designer drags something.
 */
arch('only the sequencer decides what a position is')
    ->expect('Modules\DesignFramework\Domain\ValueObjects\Position')
    ->toBeUsedIn([
        'Modules\DesignFramework\Application\Services\ContentSequencer',
        'Modules\DesignFramework\Domain\Models\ChecklistItem',
        'Modules\DesignFramework\Domain\Models\DesignPhaseDefinition',
        'Modules\DesignFramework\Domain\Models\PhaseContent',
    ]);

/**
 * The elevated-permission mechanism is temporary, and keeping it in one file is what makes replacing it
 * with the Administration context a small change rather than an audit.
 */
arch('only the policy asks who administers frameworks')
    ->expect('Modules\DesignFramework\Infrastructure\Authorization\FrameworkAdministrators')
    ->toBeUsedIn([
        'Modules\DesignFramework\Domain\Policies\FrameworkPolicy',
        'Modules\DesignFramework\Presentation\Http\Controllers\Web\FrameworkController',
    ]);

/**
 * Nothing outside the module gets to touch a framework table directly. Later contexts react to events;
 * the moment Gamification writes its own `GameFramework::where(...)`, the ownership-chain scoping has
 * two homes.
 */
arch('nothing outside the design framework reaches for its models or repositories')
    ->expect([
        'Modules\DesignFramework\Domain\Models',
        'Modules\DesignFramework\Infrastructure\Persistence',
    ])
    ->not->toBeUsedIn([
        'Modules\Identity',
        'Modules\Workspace',
        'Modules\GameDesign',
        'Modules\Playtesting',
        'App\Http\Middleware',
    ]);

/**
 * The inverse of the dependency rule, checked from the other side. The three modules DesignFramework is
 * built on must not have learned that it exists — which is what keeps them buildable, testable and
 * extractable without it.
 */
arch('the contexts below have not learned about the design framework')
    ->expect([
        'Modules\Identity',
        'Modules\Workspace',
        'Modules\GameDesign',
    ])
    ->not->toUse('Modules\DesignFramework');
