---
paths:
  - 'app/ControlHistory/**'
---

# Control History

## Institutional history remains append-only and payload-free
ResolveInstitutionalControlHistory projects chronology from eligibility, closure decisions, and reconciliation. It preserves source reference, actor, time, and state; reports chronology gaps; excludes payloads; and never creates authority or rewrites source records.

## Integrity anchors are deterministic projections
Control History integrity uses payload-free identity fields and fixed occurred_at/event_kind/event_key ordering to derive SHA-256 event and history anchors. Ordering, duplicate-key, source, and configuration problems remain visible; anchoring never mutates source history or grants authority.
