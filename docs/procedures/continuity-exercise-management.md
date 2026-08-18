# Procedure: Continuity Exercise Management

**Governing policy:** Business Continuity and Disaster Recovery; Authority and Delegation; Information Security

**Authorized roles:** continuity owner, Responsible Partner for Client exercises, exercise coordinator, recovery personnel, independent verifier, delegated closure authority

## Prerequisites

- defined Firm context or an Open Client Engagement and Client Mandate;
- explicit service scope and dependency map;
- sourced and approved recovery objectives;
- a safe exercise target and data-handling boundary;
- operative governing policies and competent approval authority.

## Procedure

1. Define the scenario, services, systems, environments, data classification, exclusions, participants, dependencies, and success criteria.
2. Record each applicable RTO and RPO with source, approval, and evidence; do not fill missing objectives by assumption.
3. Select and evidence the authorized backup and recovery point where restoration is in scope, without treating backup status as proof of restorability.
4. Approve the exact scope, objectives, risks, safe boundary, communications, and schedule.
5. Execute only inside the approved window and record attributable actions, decisions, deviations, and timestamps.
6. Restore only to the approved isolated target in this initial procedure. Record integrity, usability, security, dependency, and cleanup results.
7. Measure observed recovery time and recovery-point age for every approved objective.
8. Have a qualified person other than the exercise coordinator independently verify results and identify material gaps.
9. Link every material gap to a canonical Corrective Action; do not hide missed objectives or failed exercise results.
10. Dispose of or retain restored data under explicit authority, communicate results, preserve evidence, and obtain a separate closure decision.

**Required approvals:** exercise scope and objectives; safe execution boundary; Client authority where applicable; any exception; test-data retention; closure.

**Evidence:** objective approvals, dependency map, backup baseline, exercise approval, timeline, restore output, recovery observations, verification, Corrective Action links, data disposition, communications, and closure.

**Escalation:** unsafe conditions, scope deviation, production impact, security concern, missing authority, failed restoration, missed objectives, and uncontrolled test data are escalated immediately under the governing policy.

**Completion:** results are independently verified; every objective is measured or explicitly identified as unmeasured; material gaps have accountable Corrective Actions; test data is controlled; and closure is separately authorized and evidenced.
