# Formation Ratification and Initial Policy Bootstrap

## Purpose

The Firm needs a narrow constitutional bridge for its first governance controls. Ordinary policy approval depends on effective governance authority, but that authority itself depends on the Partnership Governance and Authority and Delegation policies. Formation Ratification resolves only this initial dependency.

It is not an ordinary Partner meeting, a substitute for the Partnership Agreement, or a declaration that formation is legally sufficient.

## Authority chain

```text
Counsel-confirmed executed Partnership Agreement
        +
Resolved Firm effective date
        +
Explicit consent of every Founding Partner
        +
Exact allowlisted initial Policy Versions
        +
Complete Evidence
        │
        ▼
Verified Formation Ratification
        │
        ▼
Initial Policy Approval Basis
        │
        ├── exact publication record
        ├── exact activation record
        └── declared effective date
                │
                ▼
         Operative Policy Version
```

Formation Ratification supplies an approval basis only. It does not imply publication, activation, effectiveness, implementation, or compliance.

## Eligible initial policies

The current bootstrap allowlist contains only:

- Partnership Governance Policy version 0.1.
- Authority and Delegation Policy version 0.1.

The allowlist is exact. It cannot approve a later version or an additional policy by category, similarity, or implication. Every approved version must preserve its repository path and controlled content digest.

## Consent and evidence

Every Founding Partner must have a distinct attributable consent record. Silence, attendance, drafting participation, repository access, or later conduct is not consent. Instrument execution, each consent, and the ratification record must link to complete Evidence.

The constitutional consent mechanism remains subject to Philippine counsel. The compiler refuses to emit an approval basis unless the configured rule is both institutionally resolved as unanimous Founding Partner consent and marked counsel-confirmed with a reference.

## Canonical state

`resources/institution/formation-bootstrap.json` is intentionally unresolved. It records the requirements and eligible Policy Versions but contains no executed instrument, Firm effective date, consent rule confirmation, ratification, or Evidence. The compiler reports those absences rather than manufacturing formation history.

## Boundaries

Formation Ratification does not:

- decide whether the Partnership was validly constituted;
- infer the Firm effective date;
- establish capital contributions or capital ownership;
- settle quorum, voting, deadlock, succession, or dissolution mechanics;
- activate any policy without separate publication and activation records;
- authorize operational action under an activated policy; or
- replace advice or executed instruments prepared by Philippine counsel.

The next formation boundary should resolve legal and administrative commencement facts before formation-derived offices and authority can become operative.
