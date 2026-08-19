---
paths:
  - 'modules/GameEconomy/**'
---

# Game Economy

## A balance profile belongs to a GameVersion, never to a Game
Every record in this module hangs off `balance_profiles.game_version_id`. Wood income was 2 in v1 and 3 in v2; if the numbers hung off the game, the second would overwrite the first and every playtest run against v1 would become uninterpretable.

Consequences worth keeping: every URL carries `versions/{version}` before the profile, `{version}` is GameDesign's own binding (never re-declared here — see .ai/rules/providers.md), and nothing copies a newer profile back onto an older version.

Exactly one profile per version may be `active`, enforced by the partial unique index `balance_profiles_one_active_per_version` rather than by a command. `ActivateBalanceProfile` archives whichever profile was active, under a row lock, because archived is the only legal move out of active.

## Amounts are exact decimals — never floats, on either side of the wire
Every numeric column is `decimal(20, 6)`, every read goes through the `AsQuantity` cast, and all arithmetic is `Quantity` (bcmath, scale 6). `Quantity` has no `toFloat()` and its `$value` is typed `numeric-string`; `isNumeric()` carries a `@phpstan-assert-if-true` so the bcmath calls type-check without a cast.

Amounts reach the client as strings and stay strings — `resources/js/features/game-economy` never parses one, and `isZeroAmount()` exists so a zero check does not become a `parseFloat`. Anything needing a total asks the server.

Validation uses `numeric` + `decimal:0,6` rather than `integer`: a game that pays half a coin is unusual but real.

## Analysis reports and never writes; scenarios never touch a base variable
`BalanceAnalyser` and `BalanceCalculator` are pure reads. Nothing they find changes a value and no finding is persisted — a half-built economy is full of warnings, and a tool that refused to save one would be a tool nobody could start with. `AnalyseBalanceProfile` is a command only because it dispatches `BalanceAnalysed`; screens call `GetBalanceAnalysis`, which computes the same numbers silently so a page refresh does not look like a decision.

A scenario override is a row in `scenario_variables`. There is no code path from setting one to `balance_variables`, so "a scenario never modifies the base value" is a fact about where the data goes rather than a rule to remember.

Snapshots are immutable: no `updated_at`, no update command, no route, and `BalanceSnapshot::performUpdate()` returns false so even a stray `save()` cannot rewrite history.

## Ownership the database cannot express is proved by EconomyCatalogue
Foreign keys prove a resource, action or variable exists; only a lookup scoped by profile proves it belongs to *this* configuration. Four identifiers arrive in request bodies with no route segment — the resource a flow moves, the resource a cost or reward names, the resource/action a variable is about, and the variable a scenario overrides — and all four go through `EconomyCatalogue`, which resolves them *through* the profile so a stranger is never found rather than found and rejected.

Validation mirrors this with rule objects (`ResourceBelongsToProfile` and friends), never an `exists` clause, so the question has one definition.

One policy (`BalanceProfilePolicy`) governs all thirteen models: `configure` is the single ability nearly every write runs against. `createSnapshot` is deliberately looser — an archived profile refuses configuration and still permits a snapshot.
