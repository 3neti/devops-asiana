# Runbook: Credential Rotation

**Governing policy:** Production Access; Information Security

**Prerequisites:** authorized scope; known dependencies; secure generation and storage; rollback or overlap plan

1. Inventory consumers and validate the credential owner and target systems.
2. Generate the replacement through the approved secrets mechanism.
3. Update consumers in a sequence that preserves service and avoids disclosure.
4. Verify authentication and service health.
5. Revoke the prior credential after the approved overlap; accelerate revocation for compromise.
6. Record actors, systems, timestamps, verification, and any failed consumers without recording secret values.

**Completion:** the old credential is revoked, consumers are verified, and evidence contains no secret material.
