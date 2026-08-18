# Formation Completion and Firm Commencement

## Purpose

Partnership Formation records intended constitutional facts. Firm Commencement answers a narrower operational question:

> Is there sufficient counsel-confirmed, internally consistent, and evidenced formation truth for formation-derived Offices and assignments to become capable of activation?

The compiler records institutional readiness. It does not determine that a Philippine partnership legally exists and does not replace an executed instrument, registration, or advice from Philippine counsel.

## Commencement chain

```text
Resolved Partnership facts
        +
Counsel-confirmed legal requirement set
        +
Executed constitutional instrument
        +
Exact principal office and effective date
        +
Every Founding Partner identity
        +
Separate initial capital records
        +
Complete Evidence
        │
        ▼
Verified Firm Commencement Record
        │
        ▼
Formation-derived assignment activation basis
```

A commencement basis does not itself set a Role Assignment to Active. The assignment must still have an explicit lifecycle state and its own required Evidence. Commencement only supplies the constitutional and temporal basis that formation-derived assignments may cite.

## Counsel-confirmed requirement set

The application does not hard-code a Philippine registration sequence. `legal_requirements_rule` must identify the exact record types that counsel confirms apply to this Firm and circumstances. A Commencement Record must evidence every and only those types and preserve the counsel confirmation reference.

Changing the requirement set changes the institutional input; it is not a software inference about Philippine law.

## Capital boundary

Initial capital contributions remain separate from:

- 50/50 governance weight;
- 30/50 Partner economic allocation;
- the 20% Firm Allocation;
- compensation rights; and
- any later capital or liquidation interest.

The compiler requires one referenced initial capital record for each Founding Partner before it can produce a commencement basis. The record may refer to a controlled accounting or legal artifact without copying sensitive values into this repository. No contribution is derived from another percentage.

## Exact reconciliation

The Commencement Record preserves a snapshot of Firm name, jurisdiction, legal form, principal office, effective date, and Founding Partner identities. Any mismatch with `ResolvedPartnership` is a conflict. The legal requirement set and capital reference keys are also snapshot-controlled.

Chronology must show that the constitutional instrument was executed and applicable legal completion records existed before commencement confirmation. A verified future effective date remains scheduled; it does not create a current activation basis.

## Canonical state

`resources/institution/formation-completion.json` is intentionally unresolved. It defines the questions but contains no counsel-confirmed legal requirement set, capital initialization, Commencement Record, or Evidence. The Partnership principal office and effective date also remain unresolved.

Consequently:

- the Firm is not represented as commenced;
- no formation-derived assignment activation basis exists;
- the Managing Partner office remains recorded but non-operative; and
- application login, policy status, repository history, and operational activity cannot fill the gap.

## Boundaries

Formation Completion does not:

- prescribe statutory forms or registration steps;
- create or amend the Partnership Agreement;
- establish capital ownership or liquidation interests;
- activate a Policy or Role Assignment;
- grant Firm Authority, Client Mandate, or Specific Approval; or
- certify legal existence or regulatory compliance.
