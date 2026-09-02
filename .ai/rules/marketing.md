---
paths:
  - 'resources/js/features/marketing/**'
---

# Marketing

## The landing page is the public site, and it is read in both directions
`resources/js/pages/welcome.tsx` is a one-line re-export of `features/marketing/pages/welcome-page.tsx`; `app.tsx` gives the `welcome` page no layout, so this feature owns its own header and footer.

Every other route in the application sits behind `auth`, so the header navigates with in-page anchors (`#how-it-works`, `#workspace`, `#languages`) and only ever links out to `login`, `register` or `dashboard`. Do not link a marketing section to a workspace screen.

Amber is deliberately the one place the app steps outside its neutral tokens (`text-amber-600 dark:text-amber-400`, `bg-amber-500/10`). Keep it to accents — badges, icons, the glow — and leave surfaces on `bg-card`/`bg-muted`.

Decoration is symmetric on purpose: the hero glow is centred with `inset-x-0 mx-auto` and the grid mask is a centred ellipse, so nothing needs mirroring for Persian. Anything that does have a side uses logical offsets — the step arrow in `design-loop.tsx` is `-end-5` plus `rtl:rotate-180`, and the diagram arrow in `studio-section.tsx` points down because down means the same thing in both directions.

`language-showcase.tsx` draws one card per `config('locales.supported')` entry, each `dir` to its own direction and formatted through `Intl` for its own locale — a language added to the config appears there on its own. Its labels are icons because a card written in Persian cannot borrow a label from the English catalogue.
