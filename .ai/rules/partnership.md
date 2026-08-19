---
paths:
    - 'app/Partnership/**'
---

# Partnership

## Resolve before projecting agreements

Partnership Agreement text and UI are projections of ResolvedPartnership, not canonical truth. ResolvePartnership must validate, detect conflicts and gaps, and surface counsel review without inventing missing institutional decisions.

## Agreement is a deterministic projection of ResolvedPartnership
CompilePartnershipAgreement consumes canonical PartnershipDefinition and ResolvedPartnership, emits a working-draft Markdown projection with fingerprints and visible unresolved/counsel-review items, and never mutates sources, calls an LLM, advances status, or asserts legal validity.
