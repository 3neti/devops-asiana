---
paths:
  - 'app/ControlHistory/**'
---

# Control History

## Institutional history remains append-only and payload-free
ResolveInstitutionalControlHistory projects chronology from eligibility, closure decisions, and reconciliation. It preserves source reference, actor, time, and state; reports chronology gaps; excludes payloads; and never creates authority or rewrites source records.

## Integrity anchors are deterministic projections
Control History integrity uses payload-free identity fields and fixed occurred_at/event_kind/event_key ordering to derive SHA-256 event and history anchors. Ordering, duplicate-key, source, and configuration problems remain visible; anchoring never mutates source history or grants authority.

## Anchor verification never upgrades integrity to authority
ResolveInstitutionalControlHistoryAnchorVerification only compares supplied anchors with resolved chronology. Missing, mismatched, unexpected, and duplicate inputs remain explicit findings; verification does not admit Evidence, accept risk, grant authority, or mutate history.

## Evidence links preserve snapshots without admitting artifacts
Verification Evidence Links require an exact verification snapshot, attributable linkage, chronology, reason, artifact reference, and Evidence reference. They export no payloads and do not admit Evidence, validate artifacts, accept risk, grant authority, or mutate history.
