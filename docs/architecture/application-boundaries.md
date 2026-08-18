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

The executable boundaries remain repository-backed rather than persistent. `PartnershipDefinitionRepository` loads canonical formation and constitutional facts; `ResolvePartnership` validates and projects them. `ResolveResponsibilityCoverage` combines that resolved truth with exact policy lifecycle state and a requirement catalog to expose coverage without duplicating appointments or treating Draft policy as authority. Rendered Agreements and browser surfaces are projections, never canonical truth.
