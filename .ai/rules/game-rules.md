---
paths:
  - 'modules/GameRules/**'
---

# Game Rules

## A rule set belongs to a GameVersion, and only a draft is editable
Every record in this module hangs off `rule_sets.game_version_id`. Combat used one die in v1 and two in v2; if the rules hung off the game, the second would overwrite the first and every playtest against v1 would become uninterpretable. So every URL carries `versions/{version}` before the rule set, `{version}` is GameDesign's own binding (never re-declared here — see .ai/rules/providers.md), and nothing copies a newer rule set back onto an older version.

Exactly one set per version may be `active`, enforced by the partial unique index `rule_sets_one_active_per_version`. `ActivateRuleSet` archives whichever set was active, under a row lock, and refuses outright while the validator reports any *error* — "these are the rules now" is a claim a rule that is its own ancestor makes false. Warnings never block.

The lifecycle is stricter than GameEconomy's, deliberately. An active balance profile stays tunable; an active rule *set* does not, because the rules are what a session was played under. `RuleSetStatus::allowsModification()` is true only for a draft, and `RuleWorkGuard::ensureRuleSetAcceptsChanges()` is the single enforcement point every one of the ~50 write commands calls. The way forward is `CloneRuleSet` — which is why it is one press, needs no name, and works on archived sets too. `rename` is the one ability an active set still answers: a title is a label on the document, not part of what was played.

## The module describes a board game and never plays one
Requirements are prose with a category, conditions are subject/operator/value read by people, effects are type/target/value, and every `value` column is a string so "half, rounded down" is sayable. Nothing anywhere parses, sums or evaluates one.

The shapes that keep it that way are worth preserving: `rule_triggers` has no `fires_effect_id` and no join table to one — what a trigger guards is expressed the other way round, by a phase transition naming it — and `ConditionGroup` is a flat list under one operator with nowhere to put a child group. An architecture test asserts the module uses no `ShouldQueue`, `Console\Command` or scheduler, because every one of those is a way for something to start *running* the rules.

The validator reports and never refuses. A rule set is written over weeks and is full of findings for most of that time; `ActivateRuleSet` is the single exception, and only for errors. Findings are never persisted — recomputing is cheap and always right, and a stored one immediately raises "is this still true?".

`CycleDetector` is the one implementation of "do these pointers come back here?" for all four things that can loop (rule parents, phase parents, rule references, phase reachability). Commands refuse the edge that would close a loop; the validator catches loops that predate the check, because a restore or an old clone can produce one.

## One adapter per foreign context, and the economy crosses as handles
`Infrastructure/GameDesign/GameCatalogue` is the only place a game's design versions are read. `Infrastructure/GameEconomy/EconomyDirectory` is the only file in the module that may import anything from `Modules\GameEconomy` — an architecture test names every other namespace and forbids it.

What crosses is a string. A rule action stores `economy_action_slug`, a requirement and an effect store `economy_resource_slug`, and the adapter turns a handle into an `EconomyReference` carrying a label and an already-worded summary. No cost is ever stored here, no amount is ever formatted here, and nothing can be joined to a GameEconomy table because a slug is not a foreign key. The costs on the rules screen and the balance screen are therefore the same numbers rather than two copies that agreed when somebody last looked.

A handle that names nothing resolves to an unresolved reference rather than failing, and the validator mentions it only when the version has an active balance profile at all — most rule sets are written before an economy is modelled and many studios never model one.

Ownership the database cannot express is proved by `RuleCatalogue`: eight identifiers arrive in request bodies with no route segment (both parents, the phase a rule or action happens in, both ends of a transition, a transition's condition and trigger, a requirement's or effect's owner, an outcome's condition, a referenced rule) and every one is resolved *through* the rule set, so a stranger is never found rather than found and rejected. Validation mirrors this with rule objects (`PhaseBelongsToRuleSet` and friends), never an `exists` clause. One policy (`RuleSetPolicy`) governs all sixteen models.
