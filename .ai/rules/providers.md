---
paths:
  - 'modules/*/Providers/*ServiceProvider.php'
---

# Providers

## Route::bind names are global — check for collisions across modules
Laravel keeps one binder per route parameter name, so the service provider registered LAST silently wins and 404s every route on the other chain.

`{version}` is bound twice: GameDesign resolves it through `{game}` (a game's iteration), DesignFramework through `{framework}` (an edition of a methodology). DesignFramework registers last and defers — it captures `Route::getBindingCallback('version')` before binding and hands the value back when the route has no `framework` parameter. The handoff is by value, so no type from the other module is imported.

This makes the order in `bootstrap/providers.php` load-bearing for a second reason: DesignFramework must register after GameDesign or the delegation runs the wrong way.

Before adding a `Route::bind`, grep `modules/*/Providers` for the name. The regression is held by "resolves a framework edition and a game version under the same parameter name" in tests/Feature/DesignFramework/FrameworkScreensTest.php.
