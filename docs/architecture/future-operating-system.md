# Future Institutional Operating System

## Recommended first vertical slice

Build **Policy Lifecycle and Exceptions** before Client or operational CRUD. It is narrow, establishes the authority hierarchy, and proves historical integrity.

The slice should support:

- `Policy` as the durable identity and owner;
- immutable `PolicyVersion` content and metadata;
- explicit `PolicyApproval` by an authorized person;
- lifecycle transitions from Draft through Retired;
- `PolicyException` tied to an exact version and requirement, with risk, compensating controls, approval, review, and expiry;
- `EvidenceRecord` links for approval and exception evidence.

Meaningful invariants include: content is never mutated after submission for review; Approved and Effective are distinct; an approval cannot be inferred from publication or use; supersession preserves prior versions; an exception cannot outlive its approved expiry; and an exception has its own approval.

## Subsequent slices

1. Client Acceptance and conflicts, ending in an explicit acceptance decision.
2. Engagement opening with exactly one current Responsible Partner and explicit system ownership.
3. Production Access Grant linked to Engagement and authority.
4. Change Record from request through independent verification and evidence.
5. Incident command, timeline, disclosure decisions, and corrective actions.

Each slice should add only the UI and infrastructure needed to exercise its rules. Authentication from the Laravel starter may remain, but institutional roles must not be conflated with the starter `User` model before identity requirements are designed.
