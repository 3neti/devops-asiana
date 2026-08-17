# Runbook: Backup Verification

**Governing policy:** Business Continuity and DR

**Prerequisites:** defined backup scope, owner, expected frequency, retention, and monitoring source

1. Compare expected assets and recovery points with backup inventory.
2. Confirm recent jobs completed and investigate warnings, exclusions, or unexpected size changes.
3. Verify encryption, retention, access control, and location against the approved design.
4. Sample integrity metadata or provider checks without treating them as a restore test.
5. Record gaps and corrective actions with owners and due dates.

**Completion:** scope and recent backup status are evidenced; failures are escalated. Backup verification does not prove restorability.
