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

## Break-glass Access Record

A Break-glass Access Record is the separate emergency path for temporary privilege when delay through the ordinary access procedure would increase material harm. It is never a Standard or Privileged ordinary Access Grant and never contains credential values.

```text
Request → Authorize → Activate → Observe → Expire → Remove → Disclose → Review → Close
```

Activation requires a defined emergency, Open Engagement, declared Incident, named person and account, Client Mandate, minimum scope, prohibited-action boundary, emergency risk owner, three distinct authorities, safe credential custody, MFA, independent monitoring, and a fixed non-renewable window. The authority layers are:

```text
Client Emergency Authority + Firm Emergency Authority + Independent Security Authority
```

Possession of the emergency credential proves none of these gates. The actor cannot independently approve, monitor, or review their own use. Every material action is attributable and evidenced inside the approved window.

Authority ends automatically at expiry even if technical removal is incomplete. Continued emergency need requires a new record and new approvals. Post-use control separately proves permission removal, credential rotation where required, Client and Responsible Partner disclosure, independent retrospective review, corrective-action ownership, and authorized closure.

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

## Incident Record

An Incident Record is the authoritative response history for an unplanned degradation, interruption, security concern, or material operational risk. An event or alert remains an observation until competent authority explicitly declares an Incident and records type, severity, reason, time, and evidence.

```text
Detect → Declare → Command → Contain → Recover → Verify → Review → Close
```

Every declared Incident names exactly one Incident Commander, the Engagement's one Responsible Partner, one Technical Lead, and one communication owner. These roles do not collapse: the Incident Commander coordinates response; the Responsible Partner retains professional and Client accountability; the Technical Lead directs technical work; and the communication owner issues factual authorized updates.

The timeline is chronological and attributable. Facts, hypotheses, decisions, actions, and state changes remain distinguishable. Preservation precedes destructive remediation where practicable. Notification to the Client, legal advisers, regulators, insurers, or other parties is an explicit decision with authority, reason, time, and evidence. A Client-impacting Incident cannot close without recorded Client disclosure.

Service restoration requires verification and a stability observation. It is not closure. Major, Security, and Client-impacting Incidents require a blameless post-incident review and owned, dated corrective actions. Closure is a separate authorized record after final notification decisions, applicable review, corrective-action accountability, and evidence are complete.
