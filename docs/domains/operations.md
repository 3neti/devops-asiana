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

The path varies by risk: a Standard Change uses a pre-approved definition; a Normal Change receives specific review; an Emergency Change invokes expedited authority and retrospective review. These paths share traceability and recovery requirements.

Incident response separates command, technical execution, professional accountability, and communications. Service restoration is not Incident closure. Closure requires evidence and owned corrective actions where applicable.
