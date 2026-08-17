---
paths:
  - 'modules/Workspace/**'
---

# Workspace

## The active workspace lives in the session, not on the account
Signing in lands on `workspaces.select`, which asks which workspace to work in; the choice is kept by `Modules\Workspace\Infrastructure\Session\ActiveWorkspace` under the `workspace.active` session key.

It is stored in the session rather than as a column on `users` because Identity sits below Workspace in `bootstrap/providers.php` and may not learn about tenancy — a foreign key on `users` would point the dependency the wrong way.

The `workspace.selected` middleware alias (registered in WorkspaceServiceProvider) gates `dashboard`. Anything that gate redirects to — `workspaces.select`, `workspaces.create`, `workspaces.store` — has to stay outside it, or the redirect loops. The stored address is re-checked against membership on every gated request, so it carries no authority: requests are still authorized against the workspace their own URL resolves to.

`workspaces.current` in the shared Inertia props is the URL's workspace when there is one, otherwise the chosen one. Note that `workspaces/index` and `workspaces/select` pass a `workspaces` prop of their own, which shadows the shared navigation prop on those two pages.
