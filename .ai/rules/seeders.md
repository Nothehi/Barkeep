---
paths:
  - 'database/seeders/**'
---

# Seeders

## The design vocabulary is stored in English and translated on the way out
`MechanicSeeder` writes the canonical English term, and `Str::slug()` derives the slug from it. That English string is the vocabulary's stable identity — the whole point of a shared list is that two studios using worker placement say so with the same word — so it must not be seeded in another language.

`MechanicResource` translates `name` and `description` through `__()` on the way out, so each reader sees the term in their own language. A curator's own new term has no catalogue entry and passes straight through, which is exactly `__()`'s fallback.

`MechanicSeeder::mechanics()` is public and static because tests/Unit/TranslationCatalogueTest.php reads it: these phrases never appear inside a literal `__()` call, so the catalogue guard would otherwise report all 86 as orphans. Add a term and the guard will ask for its Persian translation.

`DesignFrameworkSeeder` is deliberately NOT translated this way. A framework is authored content with a builder UI behind it — its wording belongs to whoever wrote that edition, not to the platform catalogue.

## Sample data is a worked example, seeded separately from content
`MechanicSeeder` and `DesignFrameworkSeeder` are content and ship on every install. The fictional studio is not: `SampleDataSeeder` orchestrates the six `Sample*Seeder` classes and `DatabaseSeeder` calls it only under `app()->environment('local')`. Elsewhere it is run deliberately with `php artisan db:seed --class=SampleDataSeeder`.

The sample seeders extend `SampleSeeder`, which resolves records by address (`user()` by email, `workspace()`/`game()` by slug, `version()` by number) and throws a named error when one is missing. That is what lets any single sample seeder be re-run alone, and why none of them holds another's identifier. Every row is keyed by its natural address so re-running edits rather than duplicates — `tests/Feature/SampleDataSeederTest.php` holds that, along with the cross-module references (evidence resolving to real playtests, iterations built on their own game's prototype versions).

Dates are written as day offsets through `daysAgo()`/`daysAhead()` rather than as literals, so a freshly seeded database always has work already done and sessions still to come. Balance snapshots go through `SnapshotWriter` rather than being hand-written, so a seeded snapshot cannot drift out of step with the shape the comparison screen reads.

## A second language means a second edition, not a translated one
`FaDesignFrameworkSeeder` and the `SampleFa*Seeder` classes extend their English counterparts and override only the data methods (`edition()`, `phases()`, `people()`, `workspaces()`, `games()`, `adoptions()`, `playtests()`, `prototypes()`, `iterations()`, `profiles()`, `frameworkSlug()`). Keep those methods `protected` — making one `private` silently reverts the subclass to the English content, which is how the Persian framework first seeded ten English phases under `masir-kargah`.

Persian content carries an explicit Latin `slug`; it is never derived. `Str::slug('ایده‌پردازی')` is `aydhprdazy` — a URL segment nobody can read or guess, and phase, framework, workspace and game slugs are all URL segments. Economy resource/action/variable slugs are what the analyser, scenario overrides and snapshot comparison match on, so they need to be stable too. `DesignFrameworkSeeder::address()` and `SampleEconomySeeder::address()` are the seams; `tests/Feature/SampleFaDataSeederTest.php` fails on any non-ASCII address.

`DesignFrameworkSeeder::parts()` and `::requirement()` are deliberately separate readers. A content row is [title, body, fact]; a checklist item is [title, fact] with no body. Reading an item with `parts()` puts the fact where the body goes, and "Player count decided" quietly stops being answered by the design record.

Unrelated but load-bearing: the unit tests assert English output, so they fail wholesale if `APP_LOCALE`/`APP_FALLBACK_LOCALE` is set to `fa` in `.env`. English is the catalogue key and must stay the fallback (see .ai/rules/lang.md).
