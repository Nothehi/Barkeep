---
paths:
  - 'modules/PrototypeIteration/**'
---

# Prototype Iteration

## One adapter per foreign context, and nothing else crosses
PrototypeIteration reads two other contexts, and each is confined to exactly one file. `Infrastructure/GameDesign/GameCatalogue` is the only place a game's design versions are read or a new one is cut; `Infrastructure/Playtesting/PlaytestEvidence` is the only file in the module that may import anything from `Modules\Playtesting`.

Everything else works with this module's own `PlaytestReference` and `CitedEvidence` value objects, so no Playtest, observation or feedback model reaches a command, controller or resource — and no copy of that evidence is ever stored here. Counts and excerpts shown against an attached playtest are read live at render time, which is why they always agree with the playtest's own screen.

Both lines are held by tests/Unit/PrototypeIteration/ArchitectureTest.php. The way they break is a convenient `with('playtest')` in a repository or an `exists:playtests` rule in a validator — route both through the adapter instead.

## A prototype version freezes once anything is built on it
There is no route that edits or deletes a `prototype_version`, and `DesignWorkGuard::ensurePrototypeVersionIsUnused()` refuses one that any iteration points at. A version is the answer to "what was actually on the table", so editing it rewrites what every record pointing at it says happened.

That refusal is only reasonable because cutting the next version is free: `CreatePrototypeVersion` takes no required fields and the number is allocated by the module. Keep it that way — a required field on that form is what would push somebody back towards wanting to edit v3.

Artifacts are the deliberate exception: a used version still accepts file uploads, because a print sheet filed later documents what the version was rather than changing it.
