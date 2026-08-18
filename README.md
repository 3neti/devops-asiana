# DevOps Asiana

DevOps Asiana is being established as the durable operating, governance, policy, evidence, and institutional-knowledge system of a Philippine General Partnership. The intended Firm will accept professional responsibility for operating critical technology for banks, electronic money issuers, and other institutions using ODTI or 3neti technology.

This repository is not presently a conventional SaaS product. It is documentation-first because authority, accountability, and evidence must be coherent before workflow software encodes them. Legal concepts here are institutional requirements for validation by Philippine counsel, not legal advice or final contractual language.

## Institutional architecture

```text
PARTNERSHIP AGREEMENT
        │
        ├── Partnership Governance Policy
        ├── Authority & Delegation Policy
        ├── Client Acceptance Policy
        ├── Engagement Policy
        ├── Financial Authority Policy
        ├── Information Security Policy
        ├── Production Access Policy
        ├── Change Management Policy
        ├── Incident Management Policy
        ├── Business Continuity / DR Policy
        ├── Professional Conduct Policy
        └── Partner Compensation Policy
                    │
                    ▼
              SOPs / RUNBOOKS
                    │
                    ▼
          Evidence / Audit Trails
```

The Partnership Agreement is the constitution. Policies derive authority from it. Procedures and runbooks implement policy. Evidence demonstrates that the required work, review, and approval occurred. A recorded execution never implies an approval.

## Cultural constitution

> **No Client Without Acceptance.**
>
> **No Client Work Without Engagement.**
>
> **No Access Without Authority.**
>
> **No Change Without Record.**
>
> **No Production Change Without Recovery.**
>
> **No Commitment Beyond Authority.**
>
> **No Incident Without Disclosure.**
>
> **No Partner Above Policy.**
>
> **Everything Material Leaves Evidence.**

Partner economics follow: **Originate. Serve. Build the Firm.** Clients belong to the Firm. Partnership authority is earned and is not inherited automatically. Founder rights protect the institution, not routine operations.

## Ecosystem boundary

```text
3neti                  ODTI                    Customer                  DevOps Asiana
Technology / IP   →    platform / scheme  →   owned infrastructure  →  delegated operations
```

3neti owns and develops underlying technology and intellectual property. ODTI commercializes, licenses, operates, or represents the platform ecosystem. The institutional customer normally owns its cloud accounts, domains, DNS, databases, credentials, production data, backups, and provider billing relationships. DevOps Asiana operates customer assets only within an accepted Engagement and recorded delegated authority.

## Repository map

- [`docs/vision`](docs/vision/) states the Firm's purpose and professional model.
- [`docs/constitution`](docs/constitution/) specifies the future Partnership Agreement's institutional requirements.
- [`docs/policies`](docs/policies/) contains the initial control framework.
- [`docs/procedures`](docs/procedures/) and [`docs/runbooks`](docs/runbooks/) seed repeatable execution.
- [`docs/evidence`](docs/evidence/) defines proof and record types.
- [`docs/domains`](docs/domains/) establishes the shared vocabulary and conceptual boundaries.
- [`docs/architecture`](docs/architecture/) describes future application boundaries and data principles.
- [`docs/adr`](docs/adr/) records durable decisions.
- [`docs/legal`](docs/legal/) identifies matters reserved for Philippine counsel.

## Current state

The repository now contains nineteen thin executable institutional compilers and a read-only Firm Console. Canonical formation facts live in `resources/institution/partnership.json`; `ResolvePartnership` produces a `ResolvedPartnership` with consistency, conflict, missing-decision, responsibility-gap, and counsel-review reports. The Firm Map renders Partnership, Management, Responsibility, and Economics projections from that one result.

Canonical policy identity and lifecycle metadata live in `resources/institution/policies.json`, while policy content remains in `docs/policies/`. `ResolvePolicyRegistry` admits an exact institutionally valid Decision Record as the approval basis for one Policy Version, then independently verifies exact-content publication, activation, effective date, exceptions, and Evidence. Decision eligibility, approval admission, publication, and activation never imply one another. Git history and document rendering confer no authority. The Policy Register makes those distinctions visible without granting approval authority to an authenticated application user. The full `docs/` hierarchy remains available through the generated, sanitized document browser.

Canonical Client Acceptance standards and records live in `resources/institution/client-acceptance.json`. `ResolveClientAcceptance` verifies the governing policy, required assessments, conflicts and related parties, explicit outcomes, decision authority, validity, and evidence. No Prospective Clients are invented, and the Console reports that the current Draft governing policy is not yet operative.

Canonical Engagement Opening standards and records live in `resources/institution/engagements.json`. `ResolveEngagements` cross-resolves accepted Client status, exactly one current Responsible Partner, scope, Client Mandate, risk classification and acceptance, commercial and operating boundaries, Firm approval, opening verification, exact policy versions, and evidence. No Engagements are invented. Approval and opening remain separate, and the current Draft Engagement and Authority policies prevent operative opening.

Canonical Production Access standards and records live in `resources/institution/production-access.json`. `ResolveProductionAccess` cross-resolves a named person and account against an Open Engagement, Client Mandate, least-privilege scope, risk, identity controls, Client and Firm approvals, validity, provisioning, independent verification, activity logging, exact policy versions, and evidence. Approval, provisioning, verification, activation, review, suspension, revocation, and closure remain separate institutional facts. Credential secrets are prohibited from canonical records, and break-glass access remains a separate emergency path. No Access Grants are invented while no canonical Engagement is Open and the governing policies remain Draft.

Canonical Production Change standards and records live in `resources/institution/changes.json`. `ResolveChanges` cross-resolves each Change against an Open Engagement, Client Mandate, operative policy versions, classification, risk, technical review, required approvals, recovery and backup confirmation, bounded execution window, a matching Active Access Grant for the named executor, execution, verification, communication, outcome, review, closure, Policy Exceptions, and evidence. An Access Grant never authorizes a specific Change, deployment never implies approval, and only a complete Scheduled Change inside its approved window is executable. No Change Records are invented while the institutional prerequisites remain absent or Draft.

Canonical Incident standards and records live in `resources/institution/incidents.json`. `ResolveIncidents` cross-resolves detection, explicit declaration, type, severity, an Open Engagement, the Engagement's Responsible Partner, distinct command roles, impact, chronological timeline, preservation, containment, investigation, recovery, restoration verification, Client and external notification decisions, required blameless review, corrective-action accountability, closure authority, conditional security and continuity policy, and evidence. An event never implies declaration, Client impact cannot be closed without disclosure, and service restoration never implies Incident closure. No Incident Records are invented while the governing Incident and Authority policies remain Draft.

Canonical Break-glass Access standards and records live in `resources/institution/break-glass-access.json`. `ResolveBreakGlassAccess` cross-resolves a defined emergency, Open Engagement, declared Incident, named actor and account, Client Mandate, minimum scope, emergency risk, Client, Firm, and independent security approvals, fixed activation and expiry, identity controls, activity evidence, independent monitoring, technical removal, disclosure, retrospective review, corrective actions, closure, and operative policy versions. Credential possession never creates authority, self-approval and self-review are rejected, absolute expiry ends authority, and emergency access cannot be silently extended into standing access.

Canonical Corrective Action standards and records live in `resources/institution/corrective-actions.json`. `ResolveCorrectiveActions` cross-resolves each source finding, exact governing requirement, risk, singular accountable owner, assignment, remediation plan, due-date history, overdue escalation, completion claim, independent verification, disposition, closure, and evidence. Source closure never closes remediation, owners cannot self-verify, date changes preserve history, and verification only makes a separate closure decision eligible. No Corrective Actions are invented while assignment authority remains Draft.

Canonical Continuity Exercise standards and records live in `resources/institution/continuity-exercises.json`. `ResolveContinuityExercises` cross-resolves Firm or Client context, Open Engagement and Client Mandate where applicable, explicit approved RTO/RPO, dependencies, backup baseline, recovery point, safe plan, approval, schedule, execution, isolated restore, observed recovery time and recovery-point age, independent verification, gaps, Corrective Actions, restored-data disposition, closure, and evidence. Backup success never proves restorability, missed objectives remain visible, and no Client recovery target is invented.

Canonical Responsibility Coverage requirements live in `resources/institution/responsibility-coverage.json`. `ResolveResponsibilityCoverage` derives current holders from the Partnership, resolves exact policy lifecycle state, preserves office-based and personal constitutional authority as separate sources, and reports vacancies, qualification gaps, prohibited combinations, sole-holder concentration exposure, succession gaps, and requirements pending policy activation. Draft policies do not create operative authority or live vacancies.

Canonical Institutional Identities, Roles, and Role Assignments live in `resources/institution/identity-and-roles.json`. `ResolveIdentityAndRoles` derives names and Partner status from Partnership truth, reconciles recorded holders with Responsibility Coverage, and preserves identity, Partner status, employment relationship, authentication, system accounts, Office, professional responsibility, and delegated authority as separate concepts. The eight founding assignments remain Approved but non-operative until a verified effective Firm Commencement basis is supplied; a date, login, title, or professional responsibility cannot create Firm Authority.

Canonical Firm Authority rules live in `resources/institution/authority-matrix.json`. `ResolveAuthorityMatrix` resolves each defined decision or approval against an exact constitutional or policy source, Responsibility Coverage, Institutional Identity, authority-bearing Role Assignment, lifecycle, effective time, risk or threshold boundary, separation, delegation limits, and Evidence. Firm Authority never supplies Client Mandate or Specific Approval. The current Matrix grants zero effective authority and explicitly defers seven undecided authority areas rather than inventing roles or thresholds.

Canonical Firm Decision and Approval standards and records live in `resources/institution/decision-records.json`. `ResolveDecisionRecords` supports separate single-holder and collective-governance authority bases, then preserves proposal, review, risk, decision outcome, effective period, execution permission, later execution, independent verification, and Evidence as separate facts. A collective outcome must pass through an exact, evidenced admission record; it never creates a Decision Record automatically. Execution never supplies approval, Client actions remain outside this Firm-only boundary, and no formation history or Decision Record is invented. The current ledger is empty and exposes three readiness gaps.

Canonical Partnership Meeting standards and records live in `resources/institution/governance-meetings.json`. `ResolveGovernanceMeetings` derives governing Partner weight from Partnership Formation, validates exact Reserved Matter classification, and preserves notice, attendance, quorum, conflicts, recusals, votes, abstentions, weighted outcome, authority, minutes, and Evidence. Silence is not consent, recused Partners cannot vote, and an equal split cannot acquire an invented deadlock remedy. No Meeting Records are invented; quorum, approval thresholds, and deadlock mechanics remain unresolved. A valid adopted outcome produces only a non-canonical Decision Record candidate.

Canonical Formation Ratification requirements live in `resources/institution/formation-bootstrap.json`. `ResolveFormationBootstrap` requires a counsel-confirmed executed Partnership Agreement, the exact resolved Firm effective date, explicit evidenced consent from both Founding Partners, exact controlled content for only the two allowlisted initial governance policies, valid chronology, and complete Evidence. A verified ratification supplies only an initial approval basis; the Policy Registry still requires separate publication, activation, and effective date before either policy becomes operative. No formation act, date, consent, or Evidence is invented, and the Console exposes the current unresolved state.

Canonical Formation Completion requirements live in `resources/institution/formation-completion.json`. `ResolveFormationCompletion` requires exact Partnership facts, a counsel-confirmed case-specific legal requirement set, an executed constitutional instrument, every Founding Partner, separately referenced initial capital records, chronology, and complete Evidence. Only a verified effective Commencement Record emits a basis that formation-derived Role Assignments may consume. It does not assert legal existence, prescribe Philippine registration steps, or activate an assignment automatically. Canonical commencement remains unresolved and visible in the Firm Console.

Canonical founding Role assumptions live in `resources/institution/role-activations.json`. `ResolveRoleActivations` requires one exact approved formation assignment, an effective verified Firm Commencement basis, explicit holder acceptance, independent verification, valid chronology, and separate Evidence for acceptance, verification, and activation. It may activate only the matching assignment and explicitly grants no Firm Authority. The Identity & Roles Console exposes eight candidate founding assignments and their admitted or pending state; because Commencement remains unresolved, zero are currently commencement-eligible and canonical state records no assumptions.

Canonical Role transitions live in `resources/institution/role-transitions.json`. `ResolveRoleTransitions` preserves one exact assignment snapshot and requires a competent decision, effective chronology, independent verification, and separate Evidence before projecting suspension, resignation, removal, revocation, or ending. Terminal transitions expose a vacancy; successor declarations remain pending a separate appointment and admission. Transition records never erase history or transfer authority.

Canonical Successor Appointments live in `resources/institution/successor-appointments.json`. `ResolveSuccessorAppointments` requires an effective predecessor vacancy, a new exact assignment snapshot, independent qualification, appointment approval, holder acceptance, activation chronology, independent verification, and distinct Evidence. It projects a new admitted assignment and coverage holder without rewriting history or granting Firm Authority. A successor never inherits an Office, authority, governance, capital, or compensation automatically.

Canonical Client action requests live in `resources/institution/client-mandates.json`. `ResolveClientMandates` resolves only bounded permitted actions: an Open Engagement, current Client Mandate for the exact action/system/environment, effective Firm Authority for the named actor, separate Specific Approval, and Evidence must all hold. Firm Authority does not supply Client authorization, and technical access or execution never proves permission.

Canonical Matters live in `resources/institution/matters.json`. `ResolveMatters` distinguishes a bounded piece of professional work from the Client relationship and parent Engagement. An accountable Matter requires an open Engagement, exactly one Responsible Partner reconciled with that Engagement, explicit scope, risk ownership and acceptance, escalation, and Evidence. It does not create Client Mandate, Firm Authority, or generic ticket permission.

Canonical Matter Events live in `resources/institution/matter-events.json`. `ResolveMatterEvents` records append-only decisions, changes, incidents, reviews, and closure against one Matter. Events require an attributable actor, ordered chronology, and Evidence; closure additionally requires an independent verifier. Events never create approval or authority and never erase prior Matter history.

Canonical Matter Closure links live in `resources/institution/matter-closures.json`. `ResolveMatterClosures` requires an admitted closure Event, explicit Matter and Corrective Action links, and separate Evidence. It reports outstanding remediation without closing or erasing Corrective Actions; remediation retains its own owner, verification, authority, and closure lifecycle.

Canonical Evidence Index records live in `resources/institution/evidence-index.json`. `ResolveEvidenceIndex` preserves explicit Client → Engagement → Matter → artifact → Evidence traceability paths. It is a read-only interrogation projection; missing links remain visible and the index never creates authority, approval, or workflow state.

Canonical Evidence Custody records live in `resources/institution/evidence-custody.json`. `ResolveEvidenceCustody` adds explicit source, custodian, custody history, retention review, integrity verification, and disposition facts to indexed Evidence. It does not store payloads or secrets, infer retention, or erase institutional history when Evidence is disposed or superseded.

Canonical Retention Review records live in `resources/institution/retention-reviews.json`. `ResolveRetentionReviews` requires an attributable review against known Evidence Custody and indexed Evidence. A deviation must reference an approved or active Policy Exception; review, exception approval, custody, disposition, and corrective action remain separate facts.

Canonical Retention Finding Links live in `resources/institution/retention-finding-links.json`. `ResolveRetentionFindingLinks` connects resolved remediation-bearing Retention Reviews to existing Corrective Actions with explicit attribution and Evidence. It never creates, assigns, verifies, or closes a Corrective Action.

Canonical Institutional Control Review configuration lives in `resources/institution/institutional-control-review.json`. `ResolveInstitutionalControlReview` provides a read-only summary of custody, retention, finding, and remediation gaps while preserving each source compiler as canonical. It does not grant authority, accept risk, create exceptions, or close remediation.

Canonical Control Review Evidence Export configuration lives in `resources/institution/control-review-evidence-export.json`. `ResolveControlReviewEvidenceExport` emits a stable, allowlisted, payload-free projection of the Institutional Control Review with gap provenance. It is not a generic reporting or audit platform and never changes source lifecycle or authority.

Canonical Control Review Sign-off records live in `resources/institution/control-review-signoffs.json`. `ResolveControlReviewSignoffs` records attributable review of an exact export snapshot with separate Evidence and an explicit no-approval boundary. Sign-off cannot suppress findings, accept risk, create exceptions, or close remediation.

Canonical Control Review Actions live in `resources/institution/control-review-actions.json`. `ResolveControlReviewActions` records bounded follow-up from an admitted Sign-off with explicit scope, owner, due date, authority basis, reason, and Evidence. It never creates, assigns, verifies, or closes a Corrective Action implicitly.

Canonical Control Review Action Outcomes live in `resources/institution/control-review-action-outcomes.json`. `ResolveControlReviewActionOutcomes` records progress, blocked state, completion claims, and verification references with explicit Evidence. Completion is never inferred, owner claims are not independent verification, and verification never closes remediation.

Canonical Control Review Closure Eligibility records live in `resources/institution/control-review-closure-eligibility.json`. `ResolveControlReviewClosureEligibility` reports whether completion, independent verification chronology, closure authority, and closure Evidence prerequisites hold. It never issues closure or mutates an action.

Canonical Control Review Closure Decisions live in `resources/institution/control-review-closure-decisions.json`. `ResolveControlReviewClosureDecisions` admits explicit closed, deferred, or rejected decisions against exact eligibility reviews. Closed decisions require eligibility and separate authority/Evidence; the underlying Action is never mutated by the compiler.

Canonical Control Review Closure Reconciliations live in `resources/institution/control-review-closure-reconciliations.json`. `ResolveControlReviewClosureReconciliations` compares admitted decisions with explicit downstream state and preserves discrepancies with attribution and Evidence. It never rewrites the decision, action, or remediation source.

Canonical Institutional Control History configuration lives in `resources/institution/control-history.json`. `ResolveInstitutionalControlHistory` projects an append-only, payload-free chronology across eligibility reviews, closure decisions, and closure reconciliations. It preserves source provenance and reports missing or unsupported events without creating authority, approval, closure, remediation, or workflow state.

Control History integrity uses the same resource to derive deterministic SHA-256 event and history anchors from payload-free identity fields. `ResolveInstitutionalControlHistoryIntegrity` reports ordering, duplicate-key, source, and configuration gaps without mutating history or creating authority, approval, closure, remediation, or workflow state.

Canonical Control History Anchor Verification configuration lives in `resources/institution/control-history-anchor-verification.json`. `ResolveInstitutionalControlHistoryAnchorVerification` compares supplied history and event anchors with resolved integrity output. The current canonical configuration supplies no anchors, so verification remains explicitly unresolved; comparison never admits Evidence, accepts risk, grants authority, or mutates history.

Canonical Control History Verification Evidence Links live in `resources/institution/control-history-verification-evidence-links.json`. `ResolveInstitutionalControlHistoryVerificationEvidenceLinks` associates external artifact and Evidence references with exact verification snapshots while excluding payloads. The registry is currently empty, and links never admit Evidence, accept risk, grant authority, or mutate history.

Canonical Control History Verification Link Reconciliations live in `resources/institution/control-history-verification-link-reconciliations.json`. `ResolveInstitutionalControlHistoryVerificationLinkReconciliations` compares explicit reconciliation records with known links and source verification snapshots. It preserves drift and missing-link findings without correcting sources, admitting artifacts, granting authority, accepting risk, or closing remediation.

No institutional database schema, generic workflow engine, accounting engine, or document editor has been introduced. See [`docs/architecture/partnership-compiler.md`](docs/architecture/partnership-compiler.md) and [`docs/architecture/future-operating-system.md`](docs/architecture/future-operating-system.md).

Do not yet build billing, payroll, a compensation engine, CRM, ticketing, infrastructure orchestration, monitoring, HRIS, a generic workflow engine, customer integrations, or elaborate UI. The next recommended increment is a narrow Control History Reconciliation Outcome Compiler that can classify explicit reconciliation outcomes without mutating source links or becoming an Evidence registry or audit platform.

Start with [`docs/README.md`](docs/README.md), the [`firm thesis`](docs/vision/firm-thesis.md), and the [`domain glossary`](docs/domains/glossary.md).
