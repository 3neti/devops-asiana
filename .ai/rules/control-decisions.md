---
paths:
  - 'app/ControlDecisions/**'
---

# Control Decisions

## Closure decisions remain separate admissions
ResolveControlReviewClosureDecisions requires an exact eligibility review, explicit closed/deferred/rejected outcome, authority basis, reason, decision time, and Evidence. Closed is admitted only when eligibility is true; this compiler never mutates or closes the underlying Action.
