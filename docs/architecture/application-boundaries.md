# Application Boundaries

The future Laravel application may organize a cohesive modular monolith around these domains:

| Domain       | Responsibility                                                    |
| ------------ | ----------------------------------------------------------------- |
| Governance   | meetings, proposals, decisions, votes, Reserved Matters           |
| Partnership  | people, Partner classes, capital concepts, succession events      |
| Clients      | prospects, acceptance, conflicts, related parties                 |
| Engagements  | scope, responsibility, teams, systems, commercial boundary        |
| Authority    | matrix, delegations, grants, approvals, thresholds                |
| Risk         | classification, ownership, acceptance, controls, residual risk    |
| Policies     | lifecycle, versions, approvals, exceptions, acknowledgement       |
| Procedures   | controlled procedure and runbook references                       |
| Access       | ordinary grants, emergency Break-glass authority, reviews, expiry, revocation |
| Changes      | classification, review, recovery, deployment, verification        |
| Incidents    | detection, declaration, command, timeline, disclosure, restoration, review, corrective action |
| Corrective Actions | source findings, singular ownership, due-date history, escalation, independent verification, closure |
| Continuity   | approved objectives, dependencies, backups, isolated restores, exercises, observed results, gaps |
| Evidence     | immutable index, custody, links, retention, integrity             |
| Finance      | financial authority, commitments, Firm assets and approvals       |
| Compensation | attribution and policy-driven calculations, later                 |
| Practices    | professional capability and leadership classifications            |
| People       | identity, roles, assignments, joiner/mover/leaver state           |
| Responsibility Coverage | required offices, authorities, qualifications, separation, concentration, and succession gaps |
| Vendors      | due diligence, contracts, access, review and exit                 |

Cross-domain references should use stable identifiers and explicit application services or actions. A shared workflow framework, event-sourcing platform, microservices, or package extraction is not justified at this stage.

The executable boundaries remain repository-backed rather than persistent. `PartnershipDefinitionRepository` loads canonical formation and constitutional facts; `ResolvePartnership` validates and projects them. `ResolveResponsibilityCoverage` combines that resolved truth with exact policy lifecycle state and a requirement catalog to expose required coverage. `ResolveIdentityAndRoles` then reconciles stable identities, Roles, and historical assignments against that coverage without treating login, employment, title, technical access, or professional responsibility as Firm Authority. `ResolveAuthorityMatrix` resolves a small catalog of Firm decisions and approvals through exact constitutional or policy sources, authority-bearing holder rules, lifecycle, time, boundary, and Evidence. `ResolveDecisionRecords` applies one exact effective Matrix entry and holder to a Firm proposal while preserving review, risk, outcome, effective period, later execution, verification, and Evidence as separate facts. `ResolveGovernanceMeetings` derives governing Partners and weights from Partnership truth, validates notice-through-outcome Meeting Records against explicit constitutional mechanics and collective authority, and emits non-canonical Decision Record candidates. Rendered Agreements and browser surfaces are projections, never canonical truth.

Identity resolution deliberately precedes the Authority Matrix. The Matrix may consume an operative Office or delegated-authority assignment, but still evaluates the authority domain, action, threshold or risk, source, time, and Evidence. It never converts every Role Assignment into authority. Client Mandate and Specific Approval remain independent downstream constraints and are not emitted by the Matrix.

Decision resolution deliberately follows the Authority Matrix. It consumes effective Firm Authority but cannot activate a Matrix entry, infer an approval from execution, or accept a Matrix entry that requires Client Mandate. Governance meeting mechanics, votes, quorum, recusals, and Reserved Matter outcomes remain a narrower future compiler rather than being generalized into this record layer.

Governance Meeting resolution sits beside Decision Record resolution rather than silently writing into it. A valid collective outcome produces a candidate with exact Meeting, agenda item, participant, vote, authority, time, and Evidence references. A later admission boundary must recognize collective authority without weakening the Decision Record compiler's existing single-holder authority rule or allowing duplicate outcomes.
