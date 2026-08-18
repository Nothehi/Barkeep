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
