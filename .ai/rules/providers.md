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

## PrototypeIteration avoids {version} and {playtest} on purpose
Two parameter names in routes/prototypes.php were chosen around the global binder table rather than for style.

`{prototypeVersion}` rather than `{version}`, because `{version}` is already bound twice — GameDesign resolves a game's iteration, DesignFramework an edition — and a third claim on it would silently break one of those chains. It also disambiguates a URL that carries a game version and a prototype version at once.

`{link}` rather than `{playtest}` for detaching a playtest, because `{playtest}` belongs to Playtesting: binding it here would hand this module's controllers another context's Eloquent model and defeat the single-adapter boundary. Attaching sends the id in the request body for the same reason.

## GameEconomy reuses {version} and renames three parameters around the binder table
GameEconomy binds `{profile}`, `{resourceType}`, `{flow}`, `{economyAction}`, `{cost}`, `{reward}`, `{effect}`, `{variable}`, `{scenario}`, `{override}`, `{assumption}` and `{balanceObservation}`.

It deliberately does **not** bind `{version}` — GameDesign already resolves a game's design state under that name, which is exactly what a balance profile needs, and a third claim on it would break GameDesign's chain and DesignFramework's delegation through it.

Three names were widened to avoid collisions: `{economyAction}` and `{resourceType}` rather than `{action}`/`{resource}` (both far too generic to claim globally), and `{balanceObservation}` rather than `{observation}`, which belongs to Playtesting — binding it here would break every playtest evidence route in the application.
