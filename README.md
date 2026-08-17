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

The repository now contains five thin executable institutional compilers and a read-only Firm Console. Canonical formation facts live in `resources/institution/partnership.json`; `ResolvePartnership` produces a `ResolvedPartnership` with consistency, conflict, missing-decision, responsibility-gap, and counsel-review reports. The Firm Map renders Partnership, Management, Responsibility, and Economics projections from that one result.

Canonical policy identity and lifecycle metadata live in `resources/institution/policies.json`, while policy content remains in `docs/policies/`. `ResolvePolicyRegistry` verifies version references, content integrity after review begins, explicit approvals, exception scope and expiry, and Evidence Record links. The Policy Register makes those distinctions visible without granting approval authority to an authenticated application user. The full `docs/` hierarchy remains available through the generated, sanitized document browser.

Canonical Client Acceptance standards and records live in `resources/institution/client-acceptance.json`. `ResolveClientAcceptance` verifies the governing policy, required assessments, conflicts and related parties, explicit outcomes, decision authority, validity, and evidence. No Prospective Clients are invented, and the Console reports that the current Draft governing policy is not yet operative.

Canonical Engagement Opening standards and records live in `resources/institution/engagements.json`. `ResolveEngagements` cross-resolves accepted Client status, exactly one current Responsible Partner, scope, Client Mandate, risk classification and acceptance, commercial and operating boundaries, Firm approval, opening verification, exact policy versions, and evidence. No Engagements are invented. Approval and opening remain separate, and the current Draft Engagement and Authority policies prevent operative opening.

Canonical Production Access standards and records live in `resources/institution/production-access.json`. `ResolveProductionAccess` cross-resolves a named person and account against an Open Engagement, Client Mandate, least-privilege scope, risk, identity controls, Client and Firm approvals, validity, provisioning, independent verification, activity logging, exact policy versions, and evidence. Approval, provisioning, verification, activation, review, suspension, revocation, and closure remain separate institutional facts. Credential secrets are prohibited from canonical records, and break-glass access remains a separate future emergency path. No Access Grants are invented while no canonical Engagement is Open and the governing policies remain Draft.

No institutional database schema, generic workflow engine, accounting engine, or document editor has been introduced. See [`docs/architecture/partnership-compiler.md`](docs/architecture/partnership-compiler.md) and [`docs/architecture/future-operating-system.md`](docs/architecture/future-operating-system.md).

Do not yet build billing, payroll, a compensation engine, CRM, ticketing, infrastructure orchestration, monitoring, HRIS, a generic workflow engine, customer integrations, or elaborate UI. The recommended next increment is a Production Change Record: Engagement- and mandate-scoped, risk-classified, explicitly approved, recoverable, executed, verified, and evidenced without inferring approval from deployment.

Start with [`docs/README.md`](docs/README.md), the [`firm thesis`](docs/vision/firm-thesis.md), and the [`domain glossary`](docs/domains/glossary.md).
