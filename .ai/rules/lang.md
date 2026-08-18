---
paths:
  - 'lang/**'
---

# Lang

## One catalogue serves PHP and React, keyed by English source text
`lang/fa.json` is keyed by the English phrase itself, so `__('Start designing')` in PHP and `t('Start designing')` in a component resolve through the same entry. English therefore has no catalogue at all — a missing translation falls back to the key, which is already the sentence to show. Do not add `lang/en/`.

`HandleInertiaRequests` ships the catalogue to the client through Laravel's own `translation.loader`, as an `Inertia::once()` prop keyed `translations:<locale>`. That key is what makes switching language work: it changes, the client stops recognising what it remembered, and the new catalogue comes down on the next response.

tests/Unit/TranslationCatalogueTest.php scans the source for `__()`, `t()` and `choice()` and fails on any phrase with no Persian entry, any entry nothing uses, and any translation whose `:placeholder` set differs from its key. Add the phrase to `lang/fa.json` rather than working round the test.
