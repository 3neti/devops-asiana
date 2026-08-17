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

## Implemented fifth vertical slice

The repository now includes a read-only **Production Access Grant Compiler** backed by `resources/institution/production-access.json`. It binds a named person and named account to an Open Engagement, Client Mandate, system, environment, permission set, purpose, risk, prerequisites, Client approval, Firm approval, validity, credential custody, activity logging, provisioning, verification, lifecycle control, and Evidence Records.

Only a complete `Active` grant creates usable authority. `Approved` does not mean provisioned; `Provisioned` does not mean verified or active; technical possession never substitutes for institutional authority. Privileged grants require an independent approval and an explicit high-risk boundary. Credential secrets are rejected from canonical records. Break-glass access is intentionally outside the ordinary grant lifecycle and requires a future emergency procedure.

No Access Grants are invented in canonical data. Because no canonical Engagement is Open and the Production Access, Authority and Delegation, and Information Security policies remain Draft, the Console correctly reports zero active authority and three policy-readiness gaps.

## Implemented sixth vertical slice

The repository now includes a read-only **Production Change Record Compiler** backed by `resources/institution/changes.json`. It binds request, Open Engagement, Client Mandate, Change classification, risk, technical review, approval path, recovery plan, backup confirmation, execution window, named executor, Active Access Grant, execution, verification, communication, outcome, post-implementation review, closure, Policy Exception references, and Evidence Records.

Only a complete `Scheduled` Change inside its approved window creates execution authority. An Active Access Grant allows its holder to use bounded access but never authorizes a particular alteration. Approval does not imply scheduling; scheduling does not prove execution; deployment does not imply approval; and execution does not imply verification or closure. Standard Changes require a current eligible pre-authorized definition. Normal Changes require specific Client and Firm approval. Emergency Changes require material-harm justification, expedited emergency authority, disclosure, and retrospective review without waiving recovery or evidence.

No Change Records are invented in canonical data. Because no canonical Engagement is Open, no Production Access Grant is Active, and the Change Management, Authority and Delegation, and Production Access policies remain Draft, the Console correctly reports zero executable Changes and three policy-readiness gaps.

## Recommended next vertical slice

Build **Incident Record** around declaration and professional disclosure. It should preserve event-to-incident classification, severity, Incident Commander, Responsible Partner, technical lead, communication owner, Client impact, containment, recovery, notification decisions, timeline, evidence, post-incident review, and corrective actions. Service restoration must not imply Incident closure.

## Subsequent slices

1. Break-glass Access Record with emergency justification, tightly bounded elevation, complete logging, automatic expiry, and retrospective review.
2. Corrective Action tracking that links incidents, failed Changes, reviews, owners, due dates, verification, and closure evidence.

Each slice should add only the UI and infrastructure needed to exercise its rules. Authentication from the Laravel starter may remain, but institutional roles must not be conflated with the starter `User` model before identity requirements are designed.
