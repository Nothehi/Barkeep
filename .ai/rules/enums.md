---
paths:
  - 'modules/GameDesign/Domain/Enums/**'
---

# Enums

## Game status transitions live only on GameStatus
The lifecycle matrix is `GameStatus::transitions()` and nothing else. Controllers, requests and the client never decide whether a move is legal — `ChangeGameStatus` checks the matrix under a row lock, and `GameResource` sends each game its own `available_transitions` (already worded) so the UI renders moves rather than knowing rules.

Archived is terminal and is reached only through the dedicated archive endpoint; the status endpoint excludes it on purpose so an irreversible move is not one field value away from a reversible one.

`GameStatus` (project lifecycle) and `DesignPhase` (design progress) are independent — active+prototyping and on_hold+playtesting are both valid. Design phases are ordered for display only and are never enforced; designers loop back from playtesting to prototyping routinely.
