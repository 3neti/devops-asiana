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

## Implemented fourth vertical slice

The repository now includes a read-only **Engagement Opening Compiler** backed by `resources/institution/engagements.json`. It cross-resolves each Engagement against current Client Acceptance, the resolved Partnership, exact Engagement and Authority policy versions, and Engagement Evidence Records.

The compiler preserves separate states for proposal, review, approval, opening, suspension, closure, and withdrawal. An Open record permits Client work only when the Client remains accepted; required policies are Effective; exactly one current, known Responsible Partner is evidenced; scope and exclusions are complete; a current Client Mandate identifies bounded systems, environments, requestors, and permitted actions; risk classification, ownership, acceptance authority, and evidence are explicit; commercial and operating terms are defined; Firm approval is explicit and evidenced; and a later Opening Record verifies the gate. Approval cannot be inferred from opening or execution, and approval alone does not imply that work may begin.

No Engagements are invented in canonical data. The Console exposes the ten-part opening standard, the distinction between Client Mandate, Firm Authority, and Specific Approval, the empty Engagement register, and the current Draft-policy readiness gaps.

## Recommended next vertical slice

Build **Production Access Grant** as an Engagement-scoped authority record. It should bind a named actor, current Engagement, Client Mandate, system and environment, privilege, purpose, approving authority, effective period, credential handling, revocation, review, and evidence. Technical possession of credentials must never imply an Access Grant.

## Subsequent slices

1. Change Record from request through independent verification and evidence.
2. Incident command, timeline, disclosure decisions, and corrective actions.

Each slice should add only the UI and infrastructure needed to exercise its rules. Authentication from the Laravel starter may remain, but institutional roles must not be conflated with the starter `User` model before identity requirements are designed.
