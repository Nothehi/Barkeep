---
paths:
  - 'modules/Identity/**'
---

# Identity

## Email verification is deliberately off — no mail service yet
`Features::emailVerification()` is removed from config/fortify.php and `User` no longer implements `MustVerifyEmail`, because the project has no outbound mail service. Both are needed: without the interface the `verified` middleware would redirect to `verification.notice`, a route Fortify no longer registers.

The `'verified'` middleware stays on every route group on purpose — it is inert while the interface is absent, so re-enabling verification is only the config line plus the interface. The `Verified` → `UserEmailVerified` bridge in IdentityServiceProvider is dormant for the same reason. Tests for the flow are kept and guarded with `skipUnlessFortifyHas(Features::emailVerification())`; the nine that skip are expected.

`email_verified_at` is still a column, cast, factory state (`unverified()`) and `UserResource` field, and UpdateUserProfile still nulls it when the address changes. New accounts are therefore left unverified — re-enabling verification later will lock out every existing user unless they are backfilled first.

Password reset still sends mail and is still enabled; only verification was dropped.
