# Future Institutional Operating System

## Implemented first vertical slice

The repository now includes a read-only Partnership Formation and Constitution Compiler backed by `resources/institution/partnership.json`. It resolves a `ResolvedPartnership`, validates governance and economic totals, reports open decisions, counsel-review items, structural conflicts, and responsibility gaps, and supplies the Firm Map and document browser.

This slice does not persist data, generate legal text, or claim legal validity.

## Implemented second vertical slice

The repository now includes a read-only **Policy Lifecycle and Exceptions Compiler** backed by `resources/institution/policies.json`. It treats each policy as a durable identity, retains explicit versions, verifies content digests after review begins, and requires separate approval and evidence records for operative states. Policy exceptions are scoped to an exact policy version and requirement, with their own approval, evidence, review date, and expiry.

The compiler supports:

- `Policy` as the durable identity and owner;
- immutable `PolicyVersion` content and metadata;
- explicit `PolicyApproval` by an authorized person;
- lifecycle transitions from Draft through Retired;
- `PolicyException` tied to an exact version and requirement, with risk, compensating controls, approval, review, and expiry;
- `EvidenceRecord` links for approval and exception evidence.

Enforced invariants include: content is never silently mutated after submission for review; Approved and Effective are distinct; approval cannot be inferred from publication or use; supersession preserves prior versions; an active exception cannot outlive its approved expiry; and an exception has its own approval and evidence.

The Console remains read-only. Institutional actors and authority are deliberately not inferred from the starter `User` model. Repository changes remain the controlled drafting mechanism until the identity and authority model is designed.

## Implemented third vertical slice

The repository now includes a read-only **Client Acceptance Compiler** backed by `resources/institution/client-acceptance.json`. It establishes a required review standard, links to an exact governing policy version, validates Prospective Client identity, conflicts and related parties, risk observations, decision authority, validity, and evidence, and projects an acceptance ledger without introducing CRM behavior.

No Prospective Clients are invented in canonical data. Hypothetical test fixtures prove that acceptance and rejection are explicit decisions; review activity, Engagement references, access, or performed work cannot imply acceptance. Accepted outcomes are blocked when required assessments remain unresolved or the governing policy is not Effective. The Console currently exposes that the Client Acceptance Policy remains Draft and therefore the control is not ready for operative decisions.

## Recommended next vertical slice

Build **Engagement Opening** as the controlling authorization boundary for material Client work. It should require an accepted Client, exactly one Responsible Partner, defined scope and exclusions, Client and Firm responsibilities, authorized requestors, environments and systems, Client Mandate, risk classification, approval authority, effective period, transition duties, and evidence. Opening an Engagement must remain distinct from accepting the Client.

## Subsequent slices

1. Production Access Grant linked to Engagement and authority.
2. Change Record from request through independent verification and evidence.
3. Incident command, timeline, disclosure decisions, and corrective actions.

Each slice should add only the UI and infrastructure needed to exercise its rules. Authentication from the Laravel starter may remain, but institutional roles must not be conflated with the starter `User` model before identity requirements are designed.
