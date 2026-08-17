# Runbook: Release to Production

**Governing policy:** Change Management; Production Access

**Prerequisites:** approved Change; immutable release identifier; tested artifact; authorized deployment path; recovery artifact or procedure

1. Verify artifact provenance, checks, approvals, target, window, and release notes.
2. Confirm backup or recoverability and active monitoring.
3. Deploy through the approved automation or named account.
4. Record artifact, environment, actor, timestamps, and deployment output.
5. Perform smoke and service verification; compare expected telemetry.
6. Roll back or escalate on failure, then communicate and close.

**Completion:** the approved artifact is verified in production and the Change Record contains deployment and verification evidence.
