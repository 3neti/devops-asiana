# Application Boundaries

The future Laravel application may organize a cohesive modular monolith around these domains:

| Domain                  | Responsibility                                                                                       |
| ----------------------- | ---------------------------------------------------------------------------------------------------- |
| Governance              | meetings, proposals, decisions, votes, Reserved Matters                                              |
| Partnership             | people, Partner classes, capital concepts, succession events                                         |
| Formation Bootstrap     | executed instrument, unanimous founder consent, initial policy approval basis, and evidence           |
| Formation Completion    | counsel-confirmed commencement requirements, capital references, effective facts, and evidence        |
| Clients                 | prospects, acceptance, conflicts, related parties                                                    |
| Engagements             | scope, responsibility, teams, systems, commercial boundary                                           |
| Authority               | matrix, delegations, grants, approvals, thresholds                                                   |
| Risk                    | classification, ownership, acceptance, controls, residual risk                                       |
| Policies                | lifecycle, versions, approvals, exceptions, acknowledgement                                          |
| Procedures              | controlled procedure and runbook references                                                          |
| Access                  | ordinary grants, emergency Break-glass authority, reviews, expiry, revocation                        |
| Changes                 | classification, review, recovery, deployment, verification                                           |
| Incidents               | detection, declaration, command, timeline, disclosure, restoration, review, corrective action        |
| Corrective Actions      | source findings, singular ownership, due-date history, escalation, independent verification, closure |
| Continuity              | approved objectives, dependencies, backups, isolated restores, exercises, observed results, gaps     |
| Evidence                | immutable index, custody, links, retention, integrity                                                |
| Finance                 | financial authority, commitments, Firm assets and approvals                                          |
| Compensation            | attribution and policy-driven calculations, later                                                    |
| Practices               | professional capability and leadership classifications                                               |
| People                  | identity, roles, assignments, joiner/mover/leaver state                                              |
| Responsibility Coverage | required offices, authorities, qualifications, separation, concentration, and succession gaps        |
| Role Transitions        | evidenced suspension, resignation, removal, revocation, ending, vacancy, and successor separation    |
| Successor Appointments  | separate successor appointment, qualification, acceptance, activation, vacancy closure, and evidence |
| Client Mandates         | Client authorization boundaries, action requests, Specific Approval, and permitted-action projections |
| Matters                 | bounded professional work, singular Responsible Partner, scope, risk, escalation, and evidence       |
| Matter Events           | append-only decisions, changes, incidents, reviews, closure, chronology, and Evidence               |
| Matter Closures         | independently verified closure projections linked to separate Corrective Actions               |
| Evidence Index          | explicit Client, Engagement, Matter, artifact, and Evidence traceability projections          |
| Evidence Custody        | source, custody, retention, integrity, and Evidence lifecycle facts                            |
| Retention Reviews       | attributable retention review outcomes and explicit Policy Exception references              |
| Retention Findings      | explicit links from remediation-bearing retention reviews to existing Corrective Actions     |
| Institutional Controls  | read-only cross-domain review of custody, retention, findings, and remediation gaps          |
| Control Review Export   | stable, allowlisted, payload-free projection of Institutional Control Review                  |
| Control Review Sign-off | attributable independent review of an exact export, distinct from approval or risk acceptance |
| Control Review Actions  | bounded, evidenced follow-up authorized from an admitted sign-off                          |
| Control Action Outcomes | progress, blocked state, completion claims, and verification references without inferred closure |
| Control Closure Eligibility | prerequisite report for action closure, without issuing closure decisions              |
| Vendors                 | due diligence, contracts, access, review and exit                                                    |

Cross-domain references should use stable identifiers and explicit application services or actions. A shared workflow framework, event-sourcing platform, microservices, or package extraction is not justified at this stage.

The executable boundaries remain repository-backed rather than persistent. `PartnershipDefinitionRepository` loads canonical formation and constitutional facts; `ResolvePartnership` validates and projects them. `ResolveFormationBootstrap` combines that result with an explicit counsel-confirmed formation act, unanimous evidenced Founding Partner consent, and exact allowlisted initial Policy Versions. It may emit a narrow approval basis for only the first governance and authority policies. `ResolveFormationCompletion` independently reconciles the counsel-confirmed legal requirement set, executed instrument, principal office, effective date, Founding Partners, initial capital references, chronology, and Evidence. Only its verified effective projection may supply eligibility for formation-derived assignments. `ResolveRoleActivations` then admits explicit holder acceptance, independent verification, activation chronology, and distinct Evidence for one exact formation assignment. `ResolveResponsibilityCoverage` combines resolved Partnership truth with exact policy lifecycle state and a requirement catalog to expose required coverage. `ResolveIdentityAndRoles` reconciles stable identities, Roles, historical assignments, and admitted activations against that coverage without treating login, employment, title, technical access, or professional responsibility as Firm Authority. `ResolveAuthorityMatrix` resolves a small catalog of Firm decisions and approvals through exact constitutional or policy sources, authority-bearing holder rules, lifecycle, time, boundary, and Evidence. `ResolveDecisionRecords` applies one exact effective Matrix entry and holder to a Firm proposal while preserving review, risk, outcome, effective period, later execution, verification, and Evidence as separate facts. `ResolveGovernanceMeetings` derives governing Partners and weights from Partnership truth, validates notice-through-outcome Meeting Records against explicit constitutional mechanics and collective authority, and emits non-canonical Decision Record candidates. `ResolvePolicyRegistry` may then accept either one exact admitted effective Decision Record or the narrow Formation Ratification basis, verify exact-content publication, and verify a separate activation and effective date. Rendered Agreements and browser surfaces are projections, never canonical truth.

Formation bootstrap deliberately precedes policy activation. A verified ratification cannot publish or activate policy, and an authenticated application user cannot perform any of these institutional acts merely through Console access. The bootstrap source remains exact, allowlisted, attributable, evidenced, and counsel-gated.

Formation completion deliberately precedes formation-derived assignment activation. A date, executed draft, application login, policy state, or observed operation cannot substitute for the exact commencement basis. Commencement does not activate an assignment; it only supplies a verified constitutional and temporal prerequisite.

Role activation is an independent admission boundary after commencement. It snapshots one exact formation assignment, preserves the holder's acceptance and another recognized person's verification, and links distinct Evidence for acceptance, verification, and activation. Its output may make only that assignment operationally Active. The admission explicitly grants no Firm Authority; an Office merely becomes eligible for the separate Authority Matrix to evaluate.

Role transition is a separate admission boundary after assignment activation or appointment. `ResolveRoleTransitions` snapshots one exact assignment, validates a competent decision, effective chronology, independent verification, and distinct Evidence, then projects only the bounded lifecycle change. Terminal transitions emit a vacancy unless a successor is separately admitted; a successor declaration never transfers authority automatically. Suspension preserves the assignment while disabling operation.

Successor appointment is a further admission boundary after a terminal Role transition. `ResolveSuccessorAppointments` requires an effective predecessor vacancy, an exact new assignment snapshot, independent Role qualification, attributable appointment approval, holder acceptance, activation chronology, independent verification, and separate Evidence for each material fact. Its projection supplies a new assignment and activation admission, overrides coverage only for the newly admitted holder, and grants no Firm Authority. The canonical identity-and-role registry remains unchanged; vacancy closure is visible only when the successor is independently admitted.

Client Mandate resolution is downstream of Engagement and Firm Authority resolution. `ResolveClientMandates` evaluates a bounded action request against an Engagement open for Client work, the exact Client Mandate action/system/environment boundary, an effective Authority Matrix entry and holder, separate Specific Approval, and Evidence. It emits only a permitted-action projection; it never expands an Engagement, creates Firm Authority, or treats technical access or execution as Client authorization.

Matter resolution is downstream of Engagement resolution. `ResolveMatters` distinguishes a bounded piece of professional work from both the Client relationship and its parent Engagement. It requires exactly one Responsible Partner reconciled with the Engagement, explicit scope and exclusions, risk classification and acceptance, escalation contacts and triggers, and Evidence. A Matter projection never creates an Engagement, Client Mandate, Firm Authority, or permission to perform Client work.

Matter Event resolution is downstream of Matter resolution. `ResolveMatterEvents` records decisions, changes, incidents, reviews, and closure against one known Matter, preserving actor, chronology, disposition, and Evidence. Closure requires an independent verifier; an event never backfills approval, creates authority, or erases earlier Matter history.

Matter Closure resolution is downstream of Matter Event and Corrective Action resolution. `ResolveMatterClosures` requires an admitted closure Event, explicit Matter and Corrective Action links, and separate closure Evidence. It reports whether linked remediation is complete but never closes or erases Corrective Actions; their own accountable owner, verification, authority, and closure remain independent.

Evidence Index resolution is a read-only traceability projection. `ResolveEvidenceIndex` indexes explicit Client, Engagement, Matter, artifact, and Evidence references, preserving gaps when a path or Evidence link is missing. It answers where proof is attached to institutional work without becoming a source of authority, approval, or generic workflow state.

Evidence Custody resolution is downstream of that index. `ResolveEvidenceCustody` requires each custody record to reference indexed Evidence, identify its source and custodian, preserve attributable custody events, and state retention, integrity verification, and disposition facts separately. It stores no payloads or secrets and never erases a disposed or superseded item from the institutional index.

Retention Review resolution is downstream of Evidence Custody and the Policy Registry. `ResolveRetentionReviews` requires a known custody record, indexed review Evidence, explicit reviewer/time/basis/outcome, and an approved or active Policy Exception when a deviation is required. A review never silently extends retention, activates a policy, closes corrective action, or changes custody disposition.

Retention Finding Link resolution is downstream of Retention Reviews and Corrective Actions. `ResolveRetentionFindingLinks` accepts only a resolved review with a remediation-bearing outcome, an existing Corrective Action, explicit linkage attribution and reason, and Evidence attached to the review. Linkage never creates, assigns, verifies, or closes remediation.

Institutional Control Review resolution is downstream of those source compilers. `ResolveInstitutionalControlReview` summarizes each configured control's conflicts and gaps while preserving source categories and messages. It never grants authority, accepts risk, creates exceptions, or closes remediation; each source compiler remains canonical.

Control Review Evidence Export resolution is downstream of Institutional Control Review. `ResolveControlReviewEvidenceExport` emits a deterministic, allowlisted projection with source identity and gap provenance. Payloads and secrets are forbidden, and exporting never changes source lifecycle, authority, risk acceptance, exceptions, or remediation.

Control Review Sign-off resolution is downstream of the exact export. `ResolveControlReviewSignoffs` requires an exact export key and status snapshot, attributable reviewer/basis/time, explicit acknowledgement that review is not approval or risk acceptance, and separate Evidence. A sign-off cannot suppress export findings or create authority, exceptions, or remediation closure.

Control Review Action resolution is downstream of an admitted Sign-off. `ResolveControlReviewActions` requires a reviewed control scope, explicit owner, due date, authority basis, reason, and Evidence. It records bounded follow-up only; it never creates, assigns, verifies, or closes a Corrective Action implicitly.

Control Review Action Outcome resolution is downstream of admitted Actions. `ResolveControlReviewActionOutcomes` records progress, blocked state, completion claims, and verification references with explicit actor, time, summary, and Evidence. Completion claims are not verification, verification references are not closure authorization, and no completion or closure is inferred.

Control Review Closure Eligibility resolution is downstream of Actions and Outcomes. `ResolveControlReviewClosureEligibility` reports whether an action has a completion claim, successful independent verification after completion, explicit closure authority, and closure Evidence. It emits no closure decision and never mutates or closes an action.

Identity resolution deliberately precedes the Authority Matrix. The Matrix may consume an operative Office or delegated-authority assignment, but still evaluates the authority domain, action, threshold or risk, source, time, and Evidence. It never converts every Role Assignment into authority. Client Mandate and Specific Approval remain independent downstream constraints and are not emitted by the Matrix.

Decision resolution deliberately follows the Authority Matrix. It consumes effective Firm Authority but cannot activate a Matrix entry, infer an approval from execution, or accept a Matrix entry that requires Client Mandate. Governance meeting mechanics, votes, quorum, recusals, and Reserved Matter outcomes remain a narrower future compiler rather than being generalized into this record layer.

Governance Meeting resolution sits beside Decision Record resolution rather than silently writing into it. A valid collective outcome produces a candidate with exact Meeting, agenda item, participant, vote, authority, time, and Evidence references. `ResolveDecisionRecords` now recognizes that candidate only through an explicit, evidenced Collective Admission Record addressed to one canonical Decision Record. It rejects duplicate targets, duplicate sources, and contradictory source snapshots without weakening the existing single-holder authority path.
