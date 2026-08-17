# Runbook: Restore Test

**Governing policy:** Business Continuity and DR

**Prerequisites:** approved isolated target; authorized backup; test objective; data-handling controls; success criteria

1. Record selected recovery point, expected RPO and RTO where defined, and test start.
2. Restore into the approved isolated environment without overwriting production.
3. Validate completeness, integrity, application usability, access controls, and dependencies.
4. Measure elapsed time and identify manual steps, failures, and undocumented dependencies.
5. Securely dispose of or retain the restored copy according to authorization.
6. Record result, evidence, gaps, and corrective actions.

**Completion:** success criteria are evaluated, test data is controlled, and corrective actions have accountable owners.
