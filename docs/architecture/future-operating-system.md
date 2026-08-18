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

## Implemented seventh vertical slice

The repository now includes a read-only **Incident Record Compiler** backed by `resources/institution/incidents.json`. It binds detection, explicit declaration, Open Engagement, type, severity, major-Incident classification, Incident Commander, the Engagement's Responsible Partner, Technical Lead, communication owner, impact, chronological timeline, evidence preservation, containment, investigation, recovery, restoration verification, notification decisions, post-incident review, corrective actions, closure authority, and Evidence Records.

An event or alert does not imply declaration. Incident command does not replace Responsible Partner accountability. Client-impacting Incidents cannot close without evidenced Client disclosure. Security Incidents additionally require operative Information Security policy; Major Incidents additionally require operative Business Continuity and Disaster Recovery policy. Service restoration is a verified operational fact, not closure. A separate closure decision is permitted only after applicable disclosure decisions are final, required review is complete, corrective actions are owned and dated, and evidence is linked.

No Incident Records are invented in canonical data. Because the Incident Management and Authority and Delegation policies remain Draft, the Console correctly reports zero active response records and two base declaration-readiness gaps.

## Implemented eighth vertical slice

The repository now includes a read-only **Break-glass Access Record Compiler** backed by `resources/institution/break-glass-access.json`. It binds a defined emergency, Open Engagement, declared Incident, named actor and account, Client Mandate, bounded permissions and prohibited actions, risk, Client emergency authority, Firm emergency authority, independent security authority, identity controls, fixed activation and expiry, complete activity logging, independent monitoring, technical removal, disclosure, retrospective review, corrective action, closure, and Evidence Records.

Break-glass is not an elevated ordinary Access Grant. Credential possession does not create authority. The actor cannot approve, monitor, or retrospectively review their own use. Authority ends at the exact approved expiry even when technical cleanup remains incomplete. Continued need requires a new independently approved record; in-place extension is a conflict. Closure remains separate from expiry and requires verified permission removal, disclosure, independent review, owned corrective action, authority, and evidence.

No emergency access history or secret material is invented in canonical data. Because the Production Access, Authority and Delegation, Information Security, and Incident Management policies remain Draft, the Console correctly reports zero active emergency authority and four activation-readiness gaps.

## Implemented ninth vertical slice

The repository now includes a read-only **Corrective Action Register Compiler** backed by `resources/institution/corrective-actions.json`. It links Incidents, Changes, Break-glass reviews, Access Reviews, Policy Exceptions, and other evidenced findings to an exact governing requirement, risk, one accountable owner, explicit assignment, remediation plan, append-only due-date history, progress, escalation, completion claim, independent verification, disposition, closure, and Evidence Records.

Source and remediation lifecycles remain independent: source closure never closes or erases corrective work. An owner may claim completion but may not verify their own work. Successful verification only makes closure eligible; a separate authority and evidence record closes it. Overdue work is visible and requires escalation, while a date change requires its own authority, reason, and evidence.

No Corrective Actions are invented in canonical data. Because the Authority and Delegation Policy remains Draft, the Console correctly reports zero actions and one base assignment-readiness gap. Source-specific policies are evaluated when a corresponding record is added.

## Implemented tenth vertical slice

The repository now includes a read-only **Continuity Exercise Record Compiler** backed by `resources/institution/continuity-exercises.json`. It links Firm or Client context, Open Engagement and Client Mandate where applicable, exact policy versions, approved service-specific RTO/RPO, dependencies, backup and recovery point, safe exercise plan, approval, schedule, execution timeline, isolated restore, observed recovery time and recovery-point age, independent verification, material gaps, canonical Corrective Actions, restored-data disposition, closure, and Evidence Records.

Backup success does not prove restorability. The compiler never supplies generic Client objectives and compares observed facts without rewriting approved expectations. The exercise coordinator cannot self-verify. A partial or failed result remains useful evidence, but every material gap must link to accountable corrective work before a separate closure decision becomes eligible.

No Continuity Exercise, recovery objective, backup, or resilience claim is invented in canonical data. Because the Business Continuity and Disaster Recovery, Authority and Delegation, and Information Security policies remain Draft, the Console correctly reports zero exercises and three approval-readiness gaps.

## Implemented eleventh vertical slice

The repository now includes a read-only **Responsibility Coverage Compiler** backed by `resources/institution/responsibility-coverage.json`. It resolves constitutional offices and responsibility assignments from `ResolvedPartnership`, resolves exact policy lifecycle state from `ResolvedPolicyRegistry`, distinguishes office, personal constitutional, professional-role, delegated, and non-authority attachments, and reports live vacancies, qualification gaps, prohibited combinations, sole-holder concentration exposure, succession gaps, and requirements pending policy activation.

Draft policies expose future design requirements but do not create authority or live vacancies. Current assignments are not copied into the coverage definition. The compiler derives them from canonical Partnership truth, and concentration reporting never silently revokes an otherwise valid appointment.

## Implemented twelfth vertical slice

The repository now includes a read-only **Institutional Identity & Role Assignment Compiler** backed by `resources/institution/identity-and-roles.json`. It recognizes the two known founders through stable cross-references to Partnership Formation, defines Offices, professional-responsibility Roles, and delegated-authority Roles, and reconciles explicit Role Assignments against Responsibility Coverage.

Partner status, employment or service classification, application authentication, system accounts, Office appointment, professional responsibility, and delegated authority remain separate. A professional-responsibility assignment never grants Firm Authority by itself. An Office or delegation may contribute authority only when its assignment is Active, temporally valid, qualified, explicitly based, properly approved where required, and evidenced; delegation additionally requires bounded scope and expiry.

The eight formation-derived assignments are Approved but not Active because verified Firm Commencement remains unresolved. A Firm effective date alone is insufficient. The Console therefore exposes seven Roles pending activation, two vacancies, two unresolved relationship classifications, and zero effective Firm Authority without inventing a date, identity, successor, or delegation.

## Implemented thirteenth vertical slice

The repository now includes a read-only **Authority Matrix Compiler** backed by `resources/institution/authority-matrix.json`. It resolves seven grounded Firm actions against exact constitutional or policy sources, Responsibility Coverage, Institutional Identities, authority-bearing Role Assignments, lifecycle, effective period, risk and threshold boundary, separation, delegation limits, and Evidence.

The Matrix resolves Firm Authority only. Client Mandate and Specific Approval remain independent gates, and every entry explicitly reports that it cannot authorize a Client action. Personal Founding Partner rights resolve through Partner status; Managing Partner authority resolves through the Office; professional-responsibility Roles cannot substitute for either. Draft policies and Design entries create no authority, unresolved thresholds block authority, and an Active entry without evidence remains non-operative.

Three constitution-derived entries are Approved but inactive. Four policy-derived entries remain Design. Privileged emergency-access approval remains vacant. Seven additional authority areas—including commercial, financial, people, legal, credentials, data export, and external communication—are explicitly deferred instead of being filled with invented powers or thresholds. The current Matrix therefore grants zero effective Firm Authority.

## Implemented fourteenth vertical slice

The repository now includes a read-only **Institutional Decision and Approval Record Compiler** backed by `resources/institution/decision-records.json`. It resolves Firm governance and management decisions through an exact effective Authority Matrix entry and holder, while keeping proposal, conflicts and related-party review, risk ownership and acceptance, outcome, effective period, conditions, execution permission, later execution, independent verification, and Evidence as separate facts.

Execution cannot backfill approval. Approval may permit execution without asserting that it occurred. Verification cannot be performed by the executor or rewrite the original Decision. Matrix entries requiring Client Mandate are outside this Firm-only compiler. Known formation facts are not fabricated as historical decisions, so the canonical ledger remains empty. The Console exposes that both governing policies are Draft and no Matrix entry is effective.

## Implemented fifteenth vertical slice

The repository now includes a read-only **Governance Meeting and Partner Vote Compiler** backed by `resources/institution/governance-meetings.json`. It derives the two Founding Partners and their equal governance weight from Partnership Formation, validates its Reserved Matter catalogue against Resolved Partnership truth, and preserves notice, agenda, attendance, quorum, conflict declarations, related-party disclosure, recusals, votes, abstentions, outcome, minutes, authority, and Evidence as distinct facts.

The compiler never accepts copied meeting weights, treats silence as neither consent nor abstention, rejects votes by recused Partners, compares the recorded outcome with the weighted result, and exposes a 50/50 split as unresolved deadlock rather than manufacturing a casting vote or remedy. A fully resolved, adopted, evidenced outcome may emit a non-canonical Decision Record candidate; it never writes to the canonical ledger.

No historical Meeting Records are invented. Ordinary and Reserved Matter quorum and approval mechanics remain UNRESOLVED, the deadlock mechanism remains UNRESOLVED and subject to counsel review, both governing policies remain Draft, and collective constitutional authority remains inactive.

## Implemented sixteenth vertical slice

The Decision Record compiler now includes a read-only **Collective Governance Decision Admission Compiler** backed by `collective_admission_records` in `resources/institution/decision-records.json`. It discovers only fully resolved Governance Meeting candidates, requires one explicit and evidenced admission addressed to one existing Decision Record, and preserves an exact source snapshot containing every participant, vote tally, Authority Matrix entry, decision time, outcome Evidence, and all source Evidence references.

A candidate does not create a Decision Record. Admission does not make a decision effective or executable. Duplicate source or target admissions and contradictory snapshots are rejected. Collective authority is rechecked against the current Authority Matrix, while the existing single-holder approval path remains unchanged. Canonical state contains no invented meetings, admissions, or decisions; the Console truthfully shows zero records.

## Implemented seventeenth vertical slice

The Policy Registry now includes a read-only **Policy Approval and Activation Admission Compiler** backed by explicit approval-admission, publication, and activation records in `resources/institution/policies.json`. It discovers only institutionally valid effective Decision Records that reference one exact Policy Version. Admission preserves an exact Decision snapshot and its own Evidence; publication preserves the controlled document path and digest; activation binds both records to the declared effective date and separate Evidence.

An eligible Decision does not approve policy automatically. Approval does not imply publication or activation. Publication, Git history, rendering, and operational use do not imply effectiveness. Duplicate Decision or Policy Version admissions, contradictory snapshots, content mismatches, and activation chronology conflicts are rejected. Canonical state remains twelve Draft policies with no invented approvals, publications, or activations.

## Implemented eighteenth vertical slice

The repository now includes a read-only **Formation Ratification and Initial Policy Bootstrap Compiler** backed by `resources/institution/formation-bootstrap.json`. It cross-resolves the Partnership, a counsel-confirmed executed formation instrument, the exact Firm effective date, explicit consent from every Founding Partner, an exact allowlist containing only Partnership Governance Policy 0.1 and Authority and Delegation Policy 0.1, and complete Evidence.

A verified ratification emits only a narrow initial-policy approval basis. Publication, activation, effective date, implementation, and operational authority remain separate. Missing consent, unconfirmed legal mechanics, content mismatch, extra policies, chronology conflicts, and incomplete Evidence prevent the basis from issuing. Canonical state contains no invented instrument, date, consent, ratification, or Evidence, so the Console exposes the open formation and counsel decisions.

## Implemented nineteenth vertical slice

The repository now includes a read-only **Formation Completion and Firm Commencement Compiler** backed by `resources/institution/formation-completion.json`. It reconciles exact Partnership identity, jurisdiction, legal form, principal office, effective date and Founding Partners with a counsel-confirmed legal requirement set, executed constitutional instrument, separate initial capital references, chronology, and Evidence.

The compiler does not prescribe Philippine registration steps. Counsel must identify the exact applicable record types, and a Commencement Record must evidence every and only those types. Governance weight, Partner economics, Firm Allocation, login, policy state, and observed operations cannot prove capital initialization or legal commencement. A verified future commencement remains scheduled rather than operative.

Only a verified effective Commencement Record emits a formation-derived assignment activation basis. `ResolveIdentityAndRoles` now consumes that basis: a populated Firm effective date alone cannot activate the Managing Partner office or any formation-derived professional responsibility. Canonical state remains unresolved and emits no activation basis.

## Founding Office assumption and Role activation

The repository now includes a read-only **Founding Role Activation Compiler** backed by `resources/institution/role-activations.json`. It derives activation candidates from the canonical identity-and-role registry, makes them eligible only through an exact effective Firm Commencement basis, and requires holder acceptance, independent verification by another recognized person, valid chronology, and separate Evidence for acceptance, verification, and activation.

An admission activates only its exact assignment. Canonical assignment history remains Approved while the identity compiler projects the admitted effective lifecycle as Active. The admission itself grants no Firm Authority; a professional responsibility remains non-authoritative, while an Office becomes only eligible for separate Authority Matrix resolution. Canonical state records no assumptions, so all eight founding assignments remain pending without inferred activation.

## Role Assignment transitions

The repository now includes a read-only **Role Assignment Transition Compiler** backed by `resources/institution/role-transitions.json`. It preserves exact assignment snapshots and admits only evidenced suspension, resignation, removal, revocation, or ending records with a competent decision, effective chronology, independent verification, and separate Evidence.

Terminal transitions project a vacancy and retain the outgoing assignment's history. A possible successor is never treated as appointed or active merely because a transition names them; the compiler reports that successor as pending separate admission. Transition admissions never grant Firm Authority, and suspension disables operation without silently changing the holder or transferring the Office.

## Successor appointments and role admission

The repository now includes a read-only **Successor Appointment and Role Admission Compiler** backed by `resources/institution/successor-appointments.json`. It requires an effective predecessor vacancy, an exact new assignment snapshot, independent qualification, attributable appointment approval, holder acceptance, activation chronology, independent verification, and separate Evidence for each material fact.

The compiler projects a new assignment and activation admission without rewriting the canonical identity-and-role registry. It closes coverage only for the separately admitted holder, preserves the predecessor's ended history, and grants no Firm Authority. A successor therefore cannot inherit an Office, authority, governance, capital, or compensation merely through relationship to the outgoing holder.

## Client Mandate and Engagement-bound authority

The repository now includes a read-only **Client Mandate and Engagement-Bound Authority Compiler** backed by `resources/institution/client-mandates.json`. It evaluates action requests only when an Engagement is open for Client work, the Client Mandate covers the exact action, system, and environment, the named actor holds effective Firm Authority for that action, a separate Specific Approval exists, and Evidence is present.

The compiler emits a permitted-action projection rather than blanket permission. Firm Authority never supplies Client authorization; an Open Engagement never authorizes every action; technical access and execution never prove either gate. The canonical register contains no action requests until the Firm has an evidenced institutional matter to evaluate.

## Matter and Responsible-Partner accountability

The repository now includes a read-only **Matter and Responsible-Partner Accountability Compiler** backed by `resources/institution/matters.json`. It distinguishes a bounded piece of professional work from the Client relationship and parent Engagement, requiring an open Engagement, exactly one Responsible Partner reconciled with that Engagement, explicit scope and exclusions, risk classification and acceptance, escalation contacts and triggers, and Evidence.

An accountable Matter projection does not create an Engagement, Client Mandate, Firm Authority, or action permission. It gives the Firm a durable answer to which Partner owns professional accountability for a bounded piece of work while preserving operational and approval boundaries.

## Matter events and evidence

The repository now includes a read-only **Matter Event and Evidence Compiler** backed by `resources/institution/matter-events.json`. It preserves append-only decision, change, incident, review, and closure events against one Matter, requiring an attributable actor, valid chronology, event disposition, and separate Evidence. Closure additionally requires an independent verifier.

The compiler does not turn events into approvals or authority, and it never erases prior Matter history. An observed execution remains an event until the required review, verification, and disposition facts are separately evidenced.

## Matter closure and corrective-action links

The repository now includes a read-only **Matter Closure and Corrective-Action Link Compiler** backed by `resources/institution/matter-closures.json`. It requires an admitted closure Event, explicit Matter and Corrective Action references, and separate closure Evidence. It projects Matter closure independently from whether linked remediation is complete, keeping outstanding obligations visible.

Corrective Actions retain their own accountable owner, due-date history, completion claim, independent verification, authority, and closure. Matter closure never implies remediation closure, and remediation closure never rewrites Matter history.

## Client and Engagement Evidence Index

The repository now includes a read-only **Client and Engagement Evidence Index Compiler** backed by `resources/institution/evidence-index.json`. It preserves explicit Client, Engagement, Matter, artifact, and Evidence references so the Firm can interrogate where proof for institutional work resides.

The index is a traceability projection, not a source of authority or workflow state. Missing links remain visible, and the index never infers a Client, Engagement, Matter, approval, or Evidence record from a neighboring artifact.

## Recommended next vertical slice

The repository now includes a read-only **Evidence Custody and Retention Compiler** backed by `resources/institution/evidence-custody.json`. It resolves source and custodian facts, append-only custody history, explicit retention and review dates, integrity verification, and disposition for Evidence already present in the Evidence Index. Custody is a lifecycle projection, not document storage: payloads and secrets remain outside this boundary, and disposal or supersession never erases institutional history.

The next boundary should connect explicit retention gaps and review outcomes to Policy Exceptions and corrective action without creating a generic records-management system.

## Retention review and policy exceptions

The repository now includes a read-only **Retention Review and Policy Exception Compiler** backed by `resources/institution/retention-reviews.json`. It records an attributable review outcome against known Evidence Custody and indexed Evidence. A deviation outcome must name an already approved or active Policy Exception; the compiler never creates approval, extends retention silently, or changes custody disposition. Future work may connect review findings to Corrective Actions while retaining each lifecycle as a separate record.

Each slice should add only the UI and infrastructure needed to exercise its rules. Authentication from the Laravel starter may remain, but institutional roles must not be conflated with the starter `User` model before identity requirements are designed.

## Retention findings and corrective actions

The repository now includes a read-only **Retention Finding and Corrective-Action Link Compiler** backed by `resources/institution/retention-finding-links.json`. It links only resolved remediation-bearing Retention Reviews to existing Corrective Actions, preserving linker, time, reason, and review-attached Evidence. The link does not create, assign, verify, or close remediation; Corrective Action governance remains independent.

## Institutional control review

The repository now includes a read-only **Institutional Control Review Compiler** backed by `resources/institution/institutional-control-review.json`. It summarizes conflicts and gaps from Evidence Custody, Retention Reviews, Retention Findings, and Corrective Actions into an attention-oriented projection. The summary preserves each source category and never replaces source history, grants authority, accepts risk, creates exceptions, or closes remediation.

## Control review evidence export

The repository now includes a read-only **Control Review Evidence Export Compiler** backed by `resources/institution/control-review-evidence-export.json`. It emits a deterministic, field-allowlisted projection of the Institutional Control Review with source identity and gap provenance while excluding payloads and secrets. The export is a report projection, not a generic audit platform or a new source of institutional truth.

## Control review sign-off

The repository now includes a read-only **Control Review Sign-off Compiler** backed by `resources/institution/control-review-signoffs.json`. It records an attributable review of an exact export snapshot, requires separate Evidence, and forces the reviewer to acknowledge that sign-off is not approval or risk acceptance. Findings remain visible and source lifecycles remain unchanged.

## Control review action register

The repository now includes a read-only **Control Review Action Register Compiler** backed by `resources/institution/control-review-actions.json`. It admits bounded follow-up only when an action references an admitted Sign-off and a control within that Sign-off's reviewed scope, with explicit owner, due date, authority basis, reason, and Evidence. It is not a generic task system and does not create or close Corrective Actions implicitly.

## Control review action outcomes

The repository now includes a read-only **Control Review Action Outcome Compiler** backed by `resources/institution/control-review-action-outcomes.json`. It records progress, blocked state, completion claims, and verification references for admitted actions. Completion claims remain distinct from independent verification, and verification references never authorize closure.

## Control review closure eligibility

The repository now includes a read-only **Control Review Action Closure Eligibility Compiler** backed by `resources/institution/control-review-closure-eligibility.json`. It checks completion claim, successful independent verification chronology, explicit closure authority, and closure Evidence as separate prerequisites. Eligibility is only a report; no action is closed or mutated.

## Control review closure decisions

The repository now includes a read-only **Control Review Closure Decision Compiler** backed by `resources/institution/control-review-closure-decisions.json`. It admits explicit closed, deferred, or rejected decisions against exact eligibility reviews. A closed decision requires eligibility, authority, reason, time, and separate Evidence; the underlying Action remains an independent historical record.

## Control review closure reconciliation

The repository now includes a read-only **Control Review Closure Reconciliation Compiler** backed by `resources/institution/control-review-closure-reconciliations.json`. It compares admitted closure decisions with explicit downstream state and records discrepancies with reconciler, basis, time, and Evidence. It never rewrites decisions, actions, or remediation.

## Institutional control review history

The repository now includes a read-only **Institutional Control History Export Compiler** backed by `resources/institution/control-history.json`. It projects an append-only chronology across closure eligibility reviews, closure decisions, and closure reconciliations. Each History Event retains source kind, source reference, actor, timestamp, and state while unsupported or incomplete chronology remains visible as a gap. Payloads and secrets are excluded, and the projection never creates authority, approval, closure, remediation, or workflow state. Future work may add integrity anchors to this history without turning it into a generic audit platform.

## Institutional control history integrity

The repository now includes a read-only **Control History Integrity and Anchor Compiler** using the same canonical history definition. `ResolveInstitutionalControlHistoryIntegrity` derives deterministic SHA-256 event anchors and one ordered history anchor from payload-free identity fields. It makes unstable ordering, duplicate event keys, source mismatches, unsupported algorithms, and inherited history gaps visible without rewriting source records or creating audit workflow, authority, approval, closure, or remediation state.

## Institutional control history anchor verification

The repository now includes a read-only **Control History Anchor Verification Compiler** backed by `resources/institution/control-history-anchor-verification.json`. `ResolveInstitutionalControlHistoryAnchorVerification` compares a supplied history anchor and optional event-anchor set with the resolved integrity projection. Missing, invalid, mismatched, unexpected, and duplicate supplied anchors remain explicit findings; the canonical configuration intentionally supplies none, so the current state is not verified. Verification does not admit Evidence, accept risk, grant authority, or mutate history.

## Control history verification evidence links

The repository now includes a read-only **Control History Verification Evidence Link Compiler** backed by `resources/institution/control-history-verification-evidence-links.json`. It associates an external artifact reference and Evidence reference with an exact verification snapshot, preserving attribution, chronology, reason, and anchor identity. The canonical registry is empty; links to unverified comparisons remain visible as gaps, and no artifact payload is stored or admitted. This boundary does not grant authority, accept risk, close remediation, or create workflow state.
