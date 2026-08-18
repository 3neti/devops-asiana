---
paths:
  - 'app/Policies/**'
---

# App Policies

## Compile policy lifecycle from repository truth
Policy Markdown and resources/institution/policies.json are canonical; the Console is read-only. Never infer approval from Git, publication, use, or starter User authentication. Reviewed content is digest-locked, operative states require explicit approval evidence, and exceptions target an exact version with their own approval and expiry.

## Policy operation requires admitted approval and activation
A declared Effective status is not sufficient. Normal Policy Version operation requires an exact institutionally valid Decision Record admitted to that version, verified publication of the controlled path and digest, a matching activation/effective date, and separate Evidence. Git history, rendering, publication, approval, or use never implies another lifecycle fact; initial bootstrap remains a separate constitutional boundary.
