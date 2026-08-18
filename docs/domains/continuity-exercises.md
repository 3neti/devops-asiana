# Continuity Exercises Domain

A Continuity Exercise is a controlled institutional test of the Firm's or a Client Engagement's ability to continue or recover a defined service. It preserves the distinction between intended recovery, observed recovery, independent verification, and closure.

```text
Propose → Approve → Schedule → Exercise → Observe → Verify → Close
```

## Recovery objectives

Recovery time objective (RTO) and recovery point objective (RPO) are explicit service-specific decisions. Every executable exercise identifies their source, approver, approval time, and evidence. DevOps Asiana does not supply generic Client objectives or infer them from backup schedules, vendor claims, or prior performance.

Observed recovery time and observed recovery-point age are measured facts. The compiler compares them with the approved objectives without rewriting either value. A missed objective remains evidence and does not make the exercise record invalid.

## Backup and restoration

A successful backup job, provider integrity check, or available recovery point is an exercise input. It does not prove restorability, usable application state, dependency availability, access-control correctness, or achievable RTO/RPO.

Restore and failover exercises identify the exact recovery point, isolated target, observed timing, integrity result, security result, deviations, and final disposition of restored data. This first slice prohibits production changes inside the exercise boundary. Any future live production exercise must separately satisfy Change, Access, Client Mandate, and incident-safety controls.

## Context, dependencies, and authority

A Client exercise requires an Open Engagement and must remain within its Client Mandate. A Firm exercise may test the Firm's own institutional services. Both identify systems, environments, data classification, exclusions, scope owner, people, credentials, platforms, providers, regions, facilities, communications paths, and other material recovery dependencies.

Approval applies to the exact scope, objectives, scenario, risk, safe boundary, and schedule. Approval does not prove execution. Exercise coordination does not confer independent verification authority.

## Verification, gaps, and closure

An independent verifier evaluates execution evidence, observed results, objectives, success criteria, security controls, cleanup, and material gaps. The exercise coordinator may not self-verify.

Every material gap links to a canonical Corrective Action. An exercise may be verified as partial or failed and still become eligible for closure after all findings are accountable. Closure is a separate authorized decision confirming communication, evidence preservation, restored-data disposition, and gap accountability.
