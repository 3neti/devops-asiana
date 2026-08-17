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
| Access       | requests, grants, reviews, revocation, break glass                |
| Changes      | classification, review, recovery, deployment, verification        |
| Incidents    | detection, declaration, command, timeline, disclosure, restoration, review, corrective action |
| Continuity   | dependencies, backups, restores, exercises, objectives            |
| Evidence     | immutable index, custody, links, retention, integrity             |
| Finance      | financial authority, commitments, Firm assets and approvals       |
| Compensation | attribution and policy-driven calculations, later                 |
| Practices    | professional capability and leadership classifications            |
| People       | identity, roles, assignments, joiner/mover/leaver state           |
| Vendors      | due diligence, contracts, access, review and exit                 |

Cross-domain references should use stable identifiers and explicit application services or actions. A shared workflow framework, event-sourcing platform, microservices, or package extraction is not justified at this stage.

The first executable boundary is repository-backed rather than persistent: `PartnershipDefinitionRepository` loads canonical formation and constitutional facts; `ResolvePartnership` validates and projects them; `ResolvedPartnership` is the intermediate domain object consumed by the Firm Console. The rendered Agreement and browser are projections, never canonical truth.
