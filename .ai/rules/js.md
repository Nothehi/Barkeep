---
paths:
  - 'resources/js/**'
---

# Js

## Use logical Tailwind properties, not physical ones — Farsi is RTL
Persian is a supported locale, so the app renders under `dir="rtl"`. Write `ms-`/`me-`, `ps-`/`pe-`, `start-`/`end-`, `text-start`/`text-end`, `border-s`/`border-e`, `rounded-s-`/`rounded-e-`/`rounded-ss-` — never `ml-`, `pr-`, `left-`, `text-left`, `border-l`, `rounded-tl-`. A back-arrow icon needs `rtl:rotate-180`.

Radix does NOT read `dir` off the document. The `DropdownMenu`, `Select`, `NavigationMenu` and `ToggleGroup` wrappers in `components/ui/` pass `dir` from `useLocale()`; any new Radix root needs the same. `@radix-ui/react-direction` is a transitive dep only — do not import it.

Anything given an explicit physical side (`<Sidebar side>`, `<SheetContent side>`, a collapsed menu's `side`) must be chosen from `useLocale().direction` at the call site.

Strings declared at module scope — `Page.layout` breadcrumb titles, auth layout title/description — cannot call a hook. They are translated where they are rendered (`components/breadcrumbs.tsx`, `layouts/auth/*`), which works because the catalogue is keyed by English text. Nav-item arrays must be built inside the component, not as module constants.

## Stored content needs dir="auto"; identifiers need dir="ltr"
The page direction is the reader's, but stored values are in whatever language they were typed in. Rendering an English game name or definition inside an RTL page without isolation drags its trailing punctuation to the wrong end — the classic "RTL is broken" look.

Whenever you render a free-form stored value (name, title, description, content, outcome, notes, response, objective, hypothesis, conclusion, instructions, prompt, display_name), put `dir="auto"` on the element holding it so it picks its own direction from its first strong character. If the value has siblings in the same element, wrap it in its own `<span dir="auto">` — this matters most for text wrapped in quotes, where the quotes are neutral characters that will flip.

Values that are identifiers rather than prose — a slug, a config key in `<code>` — get `dir="ltr"`, not `auto`; they are always Latin and should never reorder.

Translated chrome does not need either: it is already in the page's language.
