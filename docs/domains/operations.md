# Operations Domain

Operations covers Production Environments, Access Grants, Changes, Incidents, continuity, backups, restores, releases, and corrective work.

## Production Access Grant

A Production Access Grant is a named, least-privileged, time-bounded authority record. It is not a credential and does not contain secret material. It must identify the person, named account, Open Engagement, Client Mandate, system, environment, permission set, purpose, risk, prerequisites, approving authorities, validity, credential custody, logging, provisioning, verification, review, revocation control, and evidence.

The lifecycle preserves distinct facts:

```text
Request → Review → Approval → Provisioning → Verification → Active → Review / Revocation
```

Only a complete and current `Active` grant permits access use. Authentication, possession of credentials, an approved request, or a provisioned account cannot independently create authority. Privileged access requires enhanced independent approval. Break-glass access is a separate emergency control path and must not be disguised as an ordinary grant.

The governing sequence is:

```text
Engagement → Authority → Operational Record → Approval → Execution → Verification → Evidence → Closure
```

For access, three authority layers must intersect:

```text
Client Mandate + Firm Authority + Specific Access Grant = bounded permission to use access
```

## Production Change Record

A Production Change Record is the bounded authority and historical record for one intentional production alteration. It preserves request, classification, risk, technical review, approvals, recovery, backup confirmation, schedule, executor, access basis, execution, verification, communications, outcome, review, closure, exceptions, and evidence as distinct facts.

```text
Request → Classify → Review → Approve → Schedule → Execute → Verify → Close
```

Only a complete `Scheduled` Change inside its approved execution window may be executed. Three authority layers must intersect:

```text
Client Mandate + Active Access Grant + Specific Change Approval = bounded Change execution authority
```

The Active Access Grant answers whether the executor may use particular access. The Change approval answers whether that person may use it for this plan, target, risk, and window. Neither substitutes for the other. Execution and deployment evidence cannot backfill a missing approval.

Every production Change requires viable recovery and a confirmed recovery point. Policy Exceptions must be explicitly referenced and current; an omitted exception cannot be inferred from operational necessity.

The path varies by risk: a Standard Change uses a pre-approved definition; a Normal Change receives specific review; an Emergency Change invokes expedited authority and retrospective review. These paths share traceability and recovery requirements.

Incident response separates command, technical execution, professional accountability, and communications. Service restoration is not Incident closure. Closure requires evidence and owned corrective actions where applicable.
