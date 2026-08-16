---
paths:
  - 'modules/GameDesign/**'
---

# Game Design

## Games resolve by explicit route binding, never scopeBindings
Game slugs are unique per workspace, so `{game}` is resolved by an explicit `Route::bind` in GameDesignServiceProvider that looks the game up through the workspace in the URL. Do not switch these routes to `scopeBindings()`: Laravel's scoped implicit binding resolves the child through a `games()` relation on Workspace, which would make Workspace reference GameDesign, invert the dependency direction and break Workspace's own architecture tests.

The binder also resolves `{workspace}` itself (explicit bindings run before implicit ones) and writes it back onto the route, so the whole request shares one Workspace instance and one membership lookup. Keep that write-back.

Security consequence worth preserving: a game in another workspace fails to resolve and 404s before any handler or policy runs.
