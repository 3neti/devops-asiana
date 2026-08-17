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

The repository now contains two thin executable institutional compilers and a read-only Firm Console. Canonical formation facts live in `resources/institution/partnership.json`; `ResolvePartnership` produces a `ResolvedPartnership` with consistency, conflict, missing-decision, responsibility-gap, and counsel-review reports. The Firm Map renders Partnership, Management, Responsibility, and Economics projections from that one result.

Canonical policy identity and lifecycle metadata live in `resources/institution/policies.json`, while policy content remains in `docs/policies/`. `ResolvePolicyRegistry` verifies version references, content integrity after review begins, explicit approvals, exception scope and expiry, and Evidence Record links. The Policy Register makes those distinctions visible without granting approval authority to an authenticated application user. The full `docs/` hierarchy remains available through the generated, sanitized document browser.

No institutional database schema, generic workflow engine, accounting engine, or document editor has been introduced. See [`docs/architecture/partnership-compiler.md`](docs/architecture/partnership-compiler.md) and [`docs/architecture/future-operating-system.md`](docs/architecture/future-operating-system.md).

Do not yet build billing, payroll, a compensation engine, CRM, ticketing, infrastructure orchestration, monitoring, HRIS, a generic workflow engine, customer integrations, or elaborate UI. The recommended next increment is Client Acceptance as an explicit, evidenced institutional decision—not a CRM record.

Start with [`docs/README.md`](docs/README.md), the [`firm thesis`](docs/vision/firm-thesis.md), and the [`domain glossary`](docs/domains/glossary.md).
