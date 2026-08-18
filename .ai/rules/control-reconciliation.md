---
paths:
  - 'app/ControlReconciliation/**'
---

# Control Reconciliation

## Closure reconciliation never rewrites sources
ResolveControlReviewClosureReconciliations compares admitted closure decisions with explicit downstream state and preserves discrepancies as findings. It requires reconciler/time/basis/Evidence and never mutates decisions, actions, or remediation.
