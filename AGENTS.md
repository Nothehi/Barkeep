<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel-octane/core rules ===

# Laravel Octane

This application uses Laravel Octane, a long-running PHP server. The application bootstraps once and handles many requests within the same process.

- Never store request-specific state in singletons or static properties, because it can leak across requests.
- Use `config('octane.server')` to detect the active driver (`swoole`, `roadrunner`, or `frankenphp`).
- Prefer scoped bindings (`$this->app->scoped()`) over singletons for per-request services.

When working on Octane-specific features (concurrency, shared tables, memory, driver configuration, testing), invoke `octane-development` for detailed rules.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>

</br>

# Barkeep — Platform Architecture & Engineering Guide

## 1. Product Vision

Barkeep is a board-game design platform combining:

1. A structured board-game design workspace.
2. A knowledge/framework system for board-game design.
3. A CMS for articles, guides, tutorials, resources, and editorial content.
4. A gamification/progression system for designer learning and practice.
5. A community layer for games, playtests, reviews, challenges, and collaboration.
6. A Super Admin control plane for platform governance and operations.
7. Analytics and AI capabilities supporting design, content, playtesting, and platform intelligence.

The platform should not be treated as a conventional CMS with a game editor attached.

The central product concept is:

> Turn board-game design methodology into an executable, measurable, iterative design workflow.

The framework should evolve from:

    Principles → Practices → Rules → Checklists → Process

into:

    Principles
        ↓
    Design Questions
        ↓
    Structured User Input
        ↓
    Validation / Diagnostics
        ↓
    Design Decisions
        ↓
    Prototype
        ↓
    Playtest Data
        ↓
    Iteration
        ↓
    Updated Design

---

# 2. Product Areas

Barkeep consists of four major product areas:

    Barkeep
    │
    ├── Public Website
    │   ├── Blog
    │   ├── Articles
    │   ├── Guides
    │   ├── Resources
    │   ├── Mechanics
    │   └── Framework
    │
    ├── Designer Workspace
    │   ├── Games
    │   ├── Design
    │   ├── Prototype
    │   ├── Playtests
    │   ├── Iterations
    │   └── Progress
    │
    ├── Community
    │   ├── Challenges
    │   ├── Reviews
    │   ├── Playtests
    │   └── Leaderboards
    │
    └── Super Admin
        ├── Dashboard
        ├── Users
        ├── Content
        ├── Framework
        ├── Games
        ├── Gamification
        ├── Community
        ├── Moderation
        ├── Analytics
        ├── System
        └── Audit Log

---

# 3. Architectural Strategy

## 3.1 Start as a Modular Monolith

Barkeep should initially be implemented as a modular monolith.

Do NOT start with microservices.

The initial infrastructure should preferably be:

    Next.js / TypeScript frontend
              ↓
    Laravel or Go API
              ↓
    PostgreSQL
              ↓
    Redis + Queue
              ↓
    Object Storage

The application should have strong internal module boundaries even though it is deployed as one application.

Potential future extraction into services is allowed only when justified by actual scale, ownership, deployment, or operational requirements.

Do not introduce distributed systems complexity before it solves a real problem.

---

# 4. Architectural Layers

The application should follow dependency inversion and layered architecture.

    Clients
        ↓
    API / BFF
        ↓
    Application Layer
        ↓
    Domain Layer
        ↓
    Infrastructure Layer

## 4.1 Client Layer

Includes:

- Public web
- Designer application
- Admin application
- Future mobile/API clients

## 4.2 Application Layer

Responsible for:

- Commands
- Queries
- Application workflows
- DTOs
- Authorization orchestration
- Transaction boundaries
- Use cases

Examples:

- CreateGame
- UpdateGameVision
- AddMechanicToGame
- CreatePrototype
- StartPlaytest
- CompletePlaytest
- PublishContent
- CompleteQuest
- GrantAchievement

Application services coordinate domains but should not contain domain rules that belong inside domain objects.

## 4.3 Domain Layer

Contains business concepts and invariants.

Major modules:

- Identity
- Content
- Framework
- GameDesign
- Playtesting
- Gamification
- Community
- Moderation
- Analytics
- Administration

## 4.4 Infrastructure Layer

Contains implementations for:

- PostgreSQL
- Redis
- Queues
- Object storage
- Search
- Email
- AI providers
- Analytics infrastructure
- External integrations

The domain must not depend directly on infrastructure implementations.

---

# 5. Domain Boundaries

The important architectural boundary is ownership of state.

A module owns the state it is responsible for changing.

Other modules may reference that state or react to events, but should not directly mutate another module's aggregates.

Example:

    Framework
        owns:
        ├── Mechanics
        ├── Principles
        ├── Exercises
        ├── Checklists
        └── Diagnostic Rules

GameDesign does NOT modify the canonical Framework Mechanic.

Instead:

    Game
        └── GameMechanic
                └── references Mechanic

This principle applies across the platform.

---

# 6. No Cross-Domain Direct Mutation

This is a core architectural rule.

BAD:

    GamificationService
        ↓
    GameRepository
        ↓
    update game

GOOD:

    GameDesign
        ↓
    GameCreated
        ↓
    Gamification
        ↓
    evaluate achievement

BAD:

    AdminController
        ↓
    UPDATE contents SET status = 'published'

GOOD:

    Admin
        ↓
    PublishContent command
        ↓
    Content domain
        ↓
    ContentPublished event

No module may directly modify another module's aggregate/state.

---

# 7. Aggregates and State Ownership

Aggregates should protect business invariants.

Examples include:

- Game
- GameVersion
- Content
- Playtest
- QuestProgress
- AchievementUnlock
- ChallengeParticipation
- Workspace
- User

Avoid creating aggregates simply because a database table exists.

An aggregate should exist when a consistency boundary or domain invariant requires it.

Do not create giant aggregates that encompass unrelated subsystems.

---

# 8. Definition vs Execution

A critical reusable pattern throughout Barkeep is separating definitions from user-specific executions.

Examples:

    QuestDefinition
        +
    QuestProgress

    AchievementDefinition
        +
    AchievementUnlock

    ChallengeDefinition
        +
    ChallengeParticipation

    ChecklistDefinition
        +
    ChecklistExecution

    FrameworkExercise
        +
    ExerciseAttempt

Definitions are platform/framework-owned concepts.

Executions represent user-specific state.

This allows definitions to evolve without corrupting historical user activity.

---

# 9. Versioning and Snapshots

Framework entities, content, and game designs require versioning where historical reproducibility matters.

Examples:

    Mechanic
        ├── v1
        ├── v2
        └── v3

A game may reference a specific mechanic version:

    GameMechanic
        mechanic_id
        mechanic_version

Changing the current Framework Mechanic must not unexpectedly rewrite historical game designs.

The same principle applies to:

- Framework principles
- Checklists
- Diagnostic rules
- Quests
- Gamification rules
- Content
- Game versions
- Playtest references

---

# 10. Game Design Domain

The central domain is the Game Design workspace.

A game can contain:

    Game
    ├── Identity
    ├── Vision
    ├── Audience
    ├── Experience
    ├── Design Goals
    ├── Constraints
    ├── Core Loop
    ├── Mechanics
    ├── Systems
    ├── Resources
    ├── Components
    ├── Rules
    ├── Player Interaction
    ├── Progression
    ├── Economy
    ├── Balance
    ├── Prototype
    ├── Playtests
    ├── Iterations
    └── Production

The framework should guide users through these stages.

Suggested high-level progression:

    Idea
      ↓
    Concept
      ↓
    Vision
      ↓
    Core Loop
      ↓
    Mechanics
      ↓
    Systems
      ↓
    Prototype
      ↓
    Playtesting
      ↓
    Analysis
      ↓
    Iteration
      ↓
    Development
      ↓
    Completed
      ↓
    Published

Games must support backtracking because iteration is fundamental.

The state machine should support:

- Forward progression
- Backtracking
- Branching
- Abandonment
- Archiving
- Completion
- Publication

---

# 11. Immutable Game Versions

Current game state is mutable.

Historical game versions should be immutable.

Example:

    Game
      ├── Version 0.1
      ├── Version 0.2
      ├── Version 0.3
      ├── Version 0.4
      └── Version 1.0

A playtest must reference the exact version tested.

Example:

    Playtest #42
    Game: Merchant's Fall
    Version: 0.8
    Players: 4

This enables:

- Reproducibility
- Rollback
- Version comparison
- Historical analytics
- Playtest interpretation
- Design archaeology

---

# 12. Core Loop and Design Tools

The application should not reduce the framework to generic text fields.

Domain-specific interactive tools should exist.

Examples:

- Core Loop Designer
- Design Canvas
- Mechanic selector
- Resource/economy model
- Player interaction model
- Progression model
- Component inventory
- Rules editor
- Design health dashboard

The Design Canvas can act as a single source of truth for the current game's high-level design.

---

# 13. Framework Domain

The Framework is platform-owned knowledge.

It should contain:

    Framework
    ├── Stages
    ├── Principles
    ├── Questions
    ├── Exercises
    ├── Checklists
    ├── Mechanics
    ├── Patterns
    ├── Templates
    ├── Diagnostics
    └── Design Rules

Framework entities should be reusable by:

- Game Design
- CMS
- Gamification
- Search
- AI
- Analytics

The Framework should be configurable without requiring code deployment wherever practical.

---

# 14. Framework as Executable Knowledge

Framework concepts should not be merely documentation.

They should contain machine-readable semantics where useful.

Example:

    DiagnosticRule

    WHEN
        game.core_loop.action_count > 7

    THEN
        create diagnostic

    SEVERITY
        warning

    MESSAGE
        "Your core loop contains many major actions."

The framework therefore becomes a domain-specific rules system.

---

# 15. Rules Engine

Create a reusable Rules Engine subsystem.

It can evaluate:

    Rules Engine
    ├── Design Rules
    ├── Diagnostic Rules
    ├── Achievement Rules
    ├── Quest Rules
    ├── XP Rules
    └── Eligibility Rules

Conceptual flow:

    Event
      ↓
    Facts
      ↓
    Rules
      ↓
    Actions

Example:

    Event:
        PlaytestCompleted

    Facts:
        playtest.duration = 45
        playtest.players = 4
        game.playtest_count = 10

    Rules:
        IF game.playtest_count >= 10
        THEN unlock "Dedicated Tester"

        IF playtest.completed
        THEN award 150 XP

Rules must be versionable if their historical interpretation matters.

---

# 16. CMS / Content Domain

The CMS is a first-class product area.

It should support:

    Posts
    Pages
    Guides
    Tutorials
    News
    Reviews
    Announcements
    Series
    Categories
    Tags
    Authors
    Media
    Drafts
    Revisions
    Scheduled publishing

Prefer a generic content model:

    Content
        id
        type
        title
        slug
        excerpt
        content_json
        status
        author_id
        featured_media_id
        published_at
        seo_title
        seo_description
        created_at
        updated_at

Do not create separate infrastructure for every content type unless their behavior genuinely differs.

---

# 17. Block-Based Content

The CMS editor should be block-based rather than a single HTML/text field.

Potential blocks:

- Paragraph
- Heading
- Image
- Gallery
- Quote
- Video
- Code
- Table
- Callout
- Checklist
- Card
- Game Mechanic
- Game Example
- Design Principle
- Design Exercise
- Framework component

Framework-native blocks should reference domain entities rather than duplicating their data.

Example:

    Article
      └── MechanicBlock
              mechanic_id = 42

This allows the CMS and Framework to remain connected.

---

# 18. Content Publishing State Machine

Content should have an explicit lifecycle:

    Draft
      ↓
    InReview
      ↓
    Approved
      ↓
    Scheduled
      ↓
    Published
      ↓
    Archived

State transitions must be controlled by domain/application rules.

Do not scatter arbitrary status mutations throughout controllers.

---

# 19. Content Collections and Learning Paths

Support collections such as:

    Board Game Design 101

    1. What Makes a Good Board Game?
    2. Finding Your Game's Core
    3. Designing the Core Loop
    4. Choosing Mechanics
    5. Designing Player Interaction
    6. Building the Economy
    7. Balancing
    8. Prototyping
    9. Playtesting
    10. Iterating

This enables the CMS to become an educational platform rather than only a blog.

---

# 20. CMS → Product Funnel

Public content should lead users into the interactive product.

Example:

    Article
      ↓
    Educational Content
      ↓
    Framework
      ↓
    Interactive Exercise
      ↓
    Create Account
      ↓
    Game Design Studio

CMS is therefore part of the product acquisition and activation funnel.

---

# 21. Gamification Domain

Gamification should reward meaningful design progress.

Do NOT optimize for arbitrary engagement.

The core principle is:

> Reward progress toward becoming a better designer, not engagement for its own sake.

Gamification consists of:

    Gamification
    ├── XP
    ├── Levels
    ├── Skills / Mastery
    ├── Quests
    ├── Achievements
    ├── Challenges
    ├── Rewards
    ├── Seasons
    └── Leaderboards

---

# 22. XP

XP should be earned through meaningful actions.

Examples:

    Complete exercise          25 XP
    Write design decision      30 XP
    Add mechanic               20 XP
    Complete design stage     100 XP
    Create prototype          200 XP
    Run playtest              150 XP
    Analyze playtest          100 XP
    Create iteration          150 XP
    Complete game             500 XP
    Publish game             1000 XP

XP rules must be configurable rather than hard-coded throughout business logic.

---

# 23. Mastery

Do not rely only on global levels.

Maintain skill-specific progression such as:

    Game Design
    Mechanics
    Balance
    Prototyping
    Playtesting
    Systems
    Economy
    Player Interaction

A designer may therefore have:

    Designer Level: 18

    Systems Design: 91%
    Mechanics: 84%
    Balance: 61%
    Testing: 74%

---

# 24. Quests

Quests should turn framework stages into actionable tasks.

Example:

    Quest: Design Your Core Loop

    □ Define player goal
    □ Define primary action
    □ Define outcome
    □ Define repeated loop
    □ Identify key decision

    Reward: 150 XP

Quest definitions and user progress must be separate entities.

---

# 25. Achievements

Achievements should represent meaningful mastery.

Examples:

    First Concept
    Core Architect
    Mechanic Explorer
    Prototype Builder
    Playtester
    Iterative Designer
    Balance Seeker
    Finished Game

Avoid trivial achievements such as "logged in 10 times."

Hidden achievements are allowed where they improve discovery and exploration.

---

# 26. Reputation vs XP

Keep these separate.

XP answers:

> How much have you done?

Reputation answers:

> How valuable are your contributions to others?

Example:

    Designer Level: 18
    XP: 12,450
    Reputation: 742
    Helpful Reviews: 31
    Playtests: 18
    Published Games: 3

---

# 27. Community Domain

Community capabilities may include:

- Public games
- Game reviews
- Comments
- Playtest feedback
- Challenges
- Design questions
- Community resources
- Leaderboards
- Designer profiles

Community activity should produce domain events where appropriate.

Do not directly modify gamification state from community controllers.

Example:

    ReviewAccepted
        ↓
    Event
        ↓
    Gamification
        ↓
    Reputation / XP evaluation

---

# 28. Moderation Domain

Moderation should be independent from normal content/game editing.

It should manage:

    Reports
    Flagged Content
    Comments
    Games
    Users
    Reviews
    Moderation Actions
    Moderation Log

Sensitive moderation operations must be audited.

---

# 29. Identity and Multi-Tenancy

Design the identity system around users and workspaces from the beginning.

Recommended model:

    User
      │
      ├── Personal Workspace
      │
      └── Organization / Team
              ├── Members
              ├── Games
              ├── Projects
              └── Resources

Do not make user ownership the only ownership mechanism.

Prefer:

    User
      ↓
    Workspace
      ↓
    Game

This supports future teams and organizations without a painful ownership migration.

---

# 30. Authorization

Use role and permission-based authorization with policies.

Potential roles:

    Super Admin
    Admin
    Editor
    Moderator
    Author
    Designer
    Playtester

Potential permissions:

    content.create
    content.update
    content.publish
    content.delete

    game.view
    game.edit
    game.moderate

    user.view
    user.suspend
    user.ban

    framework.create
    framework.update

    gamification.configure
    gamification.grant

    analytics.view
    system.configure

Authorization must be enforced at the backend/application layer.

Never rely on frontend visibility alone.

---

# 31. Authorization vs Visibility

Do not confuse authorization with resource visibility.

Example visibility:

    private
    workspace
    unlisted
    public

A resource can be public while still requiring authorization for editing.

---

# 32. Domain Events

Barkeep is naturally event-driven.

Useful domain events include:

    UserRegistered
    WorkspaceCreated

    GameCreated
    GameStageCompleted
    MechanicAddedToGame
    PrototypeCreated
    GameVersionCreated

    PlaytestStarted
    PlaytestCompleted
    PlaytestAnalyzed
    DesignIterationCreated

    ContentSubmittedForReview
    ContentApproved
    ContentPublished

    ArticleCompleted
    ExerciseCompleted
    QuestCompleted
    AchievementUnlocked
    ChallengeCompleted

Events should describe meaningful facts, not CRUD operations.

Prefer:

    PlaytestCompleted

over:

    PlaytestUpdated

---

# 33. Domain Events vs Integration Events

Keep internal domain events conceptually separate from externally published integration events.

Domain event:

    PlaytestCompleted

Integration event:

    barkeep.playtest.completed.v1

Integration events must have stable schemas and versioning.

This becomes important for future:

- Kafka
- Analytics
- External integrations
- AI workers
- Mobile clients
- Notification systems

---

# 34. Outbox Pattern

When domain events need asynchronous publication, use a transactional outbox.

Do NOT do:

    DB transaction
        ↓
    publish event

because DB success + event failure creates inconsistency.

Instead:

    ┌──────────── Transaction ──────────────┐
    │                                       │
    │ Update aggregate                      │
    │                                       │
    │ Insert OutboxEvent                    │
    │                                       │
    └───────────────────────────────────────┘
                       ↓
                 Outbox Worker
                       ↓
                   Queue/Event Bus
                       ↓
            ┌──────────┼──────────┐
            ↓          ↓          ↓
        Analytics  Gamification Notifications

PostgreSQL should be the transactional source of truth initially.

---

# 35. CQRS — Use Selectively

Do not implement full CQRS everywhere.

Use normal transactional domain models for commands.

Examples:

    CreateGame
    UpdateVision
    AddMechanic
    CreatePlaytest
    CompleteQuest

Use specialized read models for complex dashboards and analytics.

Examples:

    designer_progress
    game_health_summary
    user_engagement_daily
    content_metrics_daily
    admin_dashboard
    gamification_profile

Flow:

    Command
      ↓
    Transaction
      ↓
    Event
      ↓
    Projection
      ↓
    Read Model

CQRS is justified when it simplifies complex read requirements, not because it is fashionable.

---

# 36. Analytics Architecture

Analytics should be event-driven rather than implemented as a large CRUD domain.

Initial architecture:

    Application Events
          ↓
        Outbox
          ↓
        Queue
          ↓
    Analytics Processing

Later, when volume warrants it:

    PostgreSQL
        ↓
    Outbox
        ↓
    Kafka
        ↓
    ClickHouse
        ↓
    Metabase / BI

Important platform metrics:

    DAU
    WAU
    MAU

    New Users
    Activated Users
    Returning Users

    Games Created
    Games Completed
    Games Abandoned

    Playtests Created
    Playtests Completed

    Articles Viewed
    Framework Exercises Completed

    Free → Paid Conversion

Important design funnel:

    Signup
      ↓
    Create Game
      ↓
    Complete Vision
      ↓
    Complete Core Loop
      ↓
    Create Prototype
      ↓
    First Playtest
      ↓
    Second Playtest
      ↓
    Complete Game

Use this to identify where designers get stuck.

---

# 37. Read Models

Build specialized projections for expensive or complex screens.

Examples:

    DesignerDashboard
    GameHealthDashboard
    AdminDashboard
    ContentAnalytics
    GamificationProfile
    DesignerJourney

Do not make every dashboard query the entire normalized domain model in real time.

---

# 38. AI Architecture

AI is an infrastructure capability, not a standalone business domain.

Potential AI capabilities:

    Design Critique
    Mechanic Suggestions
    Balance Analysis
    Playtest Analysis
    Content Assistance
    Content Classification
    Recommendations

Domains request capabilities.

Example:

    GameDesign
        ↓
    AI Design Critique

    Playtesting
        ↓
    AI Playtest Analysis

    Content
        ↓
    AI Article Assistance

AI implementation details must remain outside core domain logic.

AI work should usually be asynchronous.

---

# 39. Job / Workflow Architecture

Long-running or asynchronous operations should use jobs/workers.

Examples:

- AI analysis
- Playtest analysis
- Media processing
- Thumbnail generation
- Search indexing
- Analytics processing
- Notifications
- Achievement evaluation

Typical flow:

    Command
      ↓
    Transaction
      ↓
    Domain Event
      ↓
    Job
      ↓
    Worker

Example:

    PlaytestCompleted
          ↓
    AnalyzePlaytest
          ↓
    AI
          ↓
    PlaytestAnalysisCreated
          ↓
    Gamification
          ↓
    XP / Achievement evaluation

---

# 40. Search

Create a search abstraction instead of binding the application to a specific search engine.

Example:

    SearchPort

Implementations can include:

    PostgreSQLSearch
    Meilisearch
    OpenSearch

Search across:

    Articles
    Games
    Mechanics
    Framework
    Users
    Resources

The search result model should support contextual relationships.

Example query:

    "worker placement"

can return:

    Mechanic
    Articles
    Design Principles
    Exercises
    Games using it

---

# 41. Knowledge Graph

The Framework, CMS, and Game Design systems should form a connected knowledge graph.

Example:

    Worker Placement
        ├── Related Mechanics
        ├── Principles
        ├── Exercises
        ├── Articles
        ├── Diagnostics
        └── Games

Do not introduce a graph database initially.

PostgreSQL relationships are sufficient.

Potential relationships:

    mechanic_relations
    article_mechanics
    game_mechanics
    principle_mechanics
    exercise_mechanics
    diagnostic_mechanics

The graph should be treated as a logical model, not necessarily a separate physical database.

---

# 42. Super Admin as Control Plane

The Super Admin area is a control plane.

It should manage:

    Policies
    Configuration
    Platform Operations
    Users
    Content
    Framework
    Gamification
    Moderation
    Analytics
    System Settings

Admin should NOT bypass domain rules.

BAD:

    Admin
      ↓
    direct database mutation

GOOD:

    Admin
      ↓
    Application Command
      ↓
    Domain
      ↓
    Event

The admin interface is a privileged client of the same application layer used by the rest of the system.

---

# 43. Super Admin Modules

Recommended admin navigation:

    Dashboard
    Users
    Workspaces
    Content
    Framework
    Games
    Gamification
    Community
    Moderation
    Analytics
    Feature Flags
    System
    Audit Log

---

# 44. Audit Log

All sensitive administrative actions must be auditable.

Record:

    actor
    action
    resource
    resource_id
    timestamp
    before
    after
    IP/session information where appropriate

Examples:

    Admin published article #829

    Admin changed XP reward:
        150 → 200

    Moderator suspended user #4219

    Admin updated framework principle #31

Audit logs should be append-only from the application's perspective.

---

# 45. Feature Flags

System behavior should be configurable through feature flags.

Examples:

    AI Design Assistant
    Community Challenges
    Public Games
    Playtest Analytics
    Marketplace
    Designer Teams

Feature flags should support:

- Global enable/disable
- Environment-specific values
- Gradual rollout where needed
- Safe defaults

---

# 46. Database Strategy

PostgreSQL should be the primary transactional database.

Use normalized relational tables for stable domain relationships.

Use JSON/JSONB selectively for flexible domain-specific configuration.

Example:

    game_mechanics
        id
        game_id
        mechanic_id
        configuration JSONB
        notes
        created_at
        updated_at

Do not use JSONB as a substitute for relational modeling when data needs:

- Referential integrity
- Frequent joins
- Filtering
- Constraints
- Aggregation
- Unique indexes

---

# 47. Object Storage

Use object storage for:

    Images
    Game assets
    PDFs
    Prototype files
    Videos
    CMS media
    Export files

Do not store large binary objects directly in PostgreSQL unless there is a compelling reason.

---

# 48. Redis

Use Redis for ephemeral or high-speed concerns:

- Cache
- Queue support
- Rate limiting
- Short-lived sessions/state
- Locks where necessary
- Temporary computation results

Redis should not become the source of truth for business state.

---

# 49. API Design

Prefer explicit application commands and queries over exposing domain models directly.

Example:

    POST /games
        → CreateGame

    POST /games/{id}/mechanics
        → AddMechanicToGame

    POST /games/{id}/versions
        → CreateGameVersion

    POST /playtests
        → CreatePlaytest

    POST /playtests/{id}/complete
        → CompletePlaytest

    POST /content/{id}/publish
        → PublishContent

Avoid generic endpoints such as:

    PATCH /game/{id}

when they allow arbitrary mutation of important domain state.

---

# 50. Frontend Architecture

The frontend should reflect product boundaries.

Recommended conceptual areas:

    Public
    Designer
    Admin

The UI should not contain business-critical rules that must also exist server-side.

Frontend responsibilities:

- Presentation
- Local interaction
- Form validation for UX
- Optimistic UI where safe
- Client-side state
- API consumption

Backend responsibilities:

- Authorization
- Invariants
- State transitions
- Business rules
- Persistence
- Domain events

---

# 51. Recommended Frontend Structure

Conceptually:

    app/
      public/
      designer/
      admin/

    features/
      games/
      framework/
      playtests/
      content/
      gamification/
      community/
      moderation/
      analytics/

Keep UI components reusable, but avoid creating a generic abstraction for every domain concept.

---

# 52. Recommended Backend Structure

For a modular monolith:

    src/
    ├── Identity/
    │   ├── Domain/
    │   ├── Application/
    │   ├── Infrastructure/
    │   └── Presentation/
    │
    ├── Content/
    ├── Framework/
    ├── GameDesign/
    ├── Playtesting/
    ├── Gamification/
    ├── Community/
    ├── Moderation/
    ├── Analytics/
    └── Administration/

Each module should expose a small public application-facing API.

Do not allow arbitrary access to internal classes from other modules.

---

# 53. Testing Strategy

Testing should follow architectural boundaries.

## Unit Tests

Use for:

- Domain invariants
- State transitions
- Rules
- Value objects
- Calculations
- XP rules
- Eligibility

## Application Tests

Use for:

- Commands
- Queries
- Authorization
- Workflows
- Event publication

## Integration Tests

Use for:

- PostgreSQL repositories
- Outbox
- Queue
- Search
- Object storage
- External services

## End-to-End Tests

Use for critical journeys:

    Register
      ↓
    Create Game
      ↓
    Design Core Loop
      ↓
    Create Prototype
      ↓
    Run Playtest
      ↓
    Analyze
      ↓
    Iterate

Also test:

    Author
      ↓
    Draft Article
      ↓
    Review
      ↓
    Publish

And:

    Admin
      ↓
    Configure XP Rule
      ↓
    User performs action
      ↓
    XP awarded

---

# 54. Invariants and Domain Rules

Business invariants must be protected by the domain/application layer.

Examples:

- A published content item must have required publication metadata.
- A playtest must reference a valid game version.
- A completed game version cannot be mutated.
- A game mechanic reference must point to a valid framework mechanic/version.
- XP cannot be awarded outside the configured rules.
- An achievement cannot be unlocked twice unless explicitly repeatable.
- An admin cannot perform an action without the corresponding permission.
- A workspace resource cannot be edited by unauthorized members.

Never rely solely on frontend validation.

---

# 55. Observability

Every production environment should provide:

    Structured Logs
    Metrics
    Traces
    Error Tracking

Important dimensions:

    request_id
    user_id
    workspace_id
    game_id
    playtest_id
    content_id
    job_id
    event_id

Correlate asynchronous work using IDs.

---

# 56. Reliability

Critical asynchronous operations should be idempotent.

For example:

    PlaytestCompleted

must not award XP twice because a worker retried.

Use:

- Idempotency keys
- Unique event IDs
- Transactional constraints
- Retry-safe jobs
- Dead-letter handling where appropriate

Example:

    AchievementUnlock
        UNIQUE(user_id, achievement_id)

This makes repeated processing safe.

---

# 57. Security

Security boundaries:

    Authentication
        ↓
    Authorization
        ↓
    Domain Invariants
        ↓
    Audit

Never trust:

- Client-supplied roles
- Client-supplied XP
- Client-supplied achievement state
- Client-supplied ownership
- Client-supplied publication status
- Client-supplied administrative privileges

All sensitive values must be derived or validated server-side.

---

# 58. Product-Level Design Principle

Barkeep should continuously connect:

    Learn
      ↓
    Design
      ↓
    Build
      ↓
    Test
      ↓
    Analyze
      ↓
    Improve
      ↓
    Share

The CMS handles "Learn".

The Game Design Studio handles "Design".

Prototype/Playtesting handles "Build/Test".

Analytics and AI handle "Analyze".

Iterations handle "Improve".

Community handles "Share".

Gamification connects the entire journey.

---

# 59. MVP Architecture

The first production architecture should be:

    Next.js
       ↓
    Laravel or Go modular monolith
       ↓
    PostgreSQL
       ↓
    Redis
       ↓
    Queue
       ↓
    Object Storage

Core modules:

    Identity
    Content
    Framework
    GameDesign
    Playtesting
    Gamification
    Administration

Implement from the beginning:

- Module boundaries
- Aggregate ownership
- Application commands/queries
- State machines
- Game versions
- Definition/execution separation
- Domain events
- Outbox
- Audit log
- Policy-based authorization

Do NOT initially implement:

- Microservices
- Kafka
- Kubernetes
- Graph database
- Full CQRS everywhere
- Complex event sourcing

Introduce those only when justified.

---

# 60. Evolution Path

## Phase 1 — Modular Monolith

    Next.js
    Laravel/Go
    PostgreSQL
    Redis
    Queue
    Object Storage

## Phase 2 — Strong Domain Infrastructure

Add:

    Domain Events
    Outbox
    Read Models
    Rules Engine
    Search
    Audit Log
    Feature Flags

## Phase 3 — Intelligence and Analytics

Add:

    AI Workers
    Advanced Analytics
    ClickHouse
    Event Streaming
    BI

Potential architecture:

    PostgreSQL
        ↓
    Outbox
        ↓
    Kafka
        ↓
    ClickHouse
        ↓
    Analytics / BI

## Phase 4 — Selective Service Extraction

Extract only domains that demonstrate a real need for:

- Independent scaling
- Independent deployment
- Independent infrastructure
- Strong operational isolation
- Different technology/runtime requirements

Potential future candidates:

    AI Processing
    Analytics Pipeline
    Search
    Media Processing

Do not extract domains merely because they exist.

---

# 61. Architectural Decision Rules

When making architectural decisions, apply these rules:

1. Prefer domain ownership over database ownership.
2. Prefer explicit use cases over generic CRUD.
3. Prefer domain events over direct cross-domain calls where loose coupling is valuable.
4. Prefer synchronous transactions for invariants.
5. Prefer asynchronous jobs for expensive or non-critical work.
6. Prefer immutable historical versions when reproducibility matters.
7. Prefer PostgreSQL until another datastore solves a demonstrated problem.
8. Prefer a modular monolith until operational evidence supports service extraction.
9. Prefer configuration/data-driven rules over hard-coded platform rules.
10. Keep Super Admin as a privileged client of the same domain/application layer.
11. Never bypass domain invariants for administrative convenience.
12. Keep AI outside core domain logic.
13. Keep analytics derived from events rather than tightly coupled to transactional workflows.
14. Keep XP/reputation/achievements separate from ordinary user activity data.
15. Design for workspaces even if the initial product is mostly individual users.

---

# 62. Core Architectural Mental Model

The final mental model for Barkeep is:

                           BARK​​EEP
                              │
       ┌──────────────────────┼──────────────────────┐
       │                      │                      │
    Public                  Designer                Admin
       │                      │                      │
    Content                  Games                Control Plane
    Guides                   Design               Users
    Framework                Prototype             Content
    Resources                Playtest               Framework
                             Iteration              Gamification
                             Progress               Moderation
                                                    Analytics
                              │
                              │
                       Shared Domain Platform
                              │
       ┌──────────────────────┼──────────────────────┐
       │                      │                      │
    Framework              Identity              Content
       │                      │                      │
    Mechanics              Users                 Articles
    Principles             Workspaces             Media
    Exercises              Roles                  Series
    Diagnostics             Permissions            Tags
       │
       ├───────────────┐
       │               │
    Game Design    Gamification
       │               │
    Playtesting      XP
    Versions        Quests
    Iterations      Achievements
                    Mastery
       │               │
       └───────┬───────┘
               │
          Domain Events
               │
             Outbox
               │
          Queue / Bus
               │
       ┌───────┼────────┐
       ↓       ↓        ↓
      AI    Analytics  Notifications
               │
           ClickHouse
               │
              BI

The central architectural principle is:

> Barkeep is a domain platform whose domains communicate through explicit application contracts and domain events, while PostgreSQL remains the transactional source of truth.

The architecture should optimize first for **correctness, evolvability, historical reproducibility, and domain isolation**. Scale-oriented infrastructure should be introduced only when actual system behavior justifies it.

