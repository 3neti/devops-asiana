# Partnership Formation and Constitution Compiler

The compiler treats an Agreement as a projection, not the canonical institutional model.

```text
PartnershipFormation + PartnershipConstitution
                    │
                    ▼
           ResolvePartnership
                    │
                    ▼
          ResolvedPartnership
          ├─ consistency checks
          ├─ conflict detection
          ├─ missing decisions
          ├─ responsibility coverage
          └─ counsel review
                    │
                    ▼
     Agreement / manifest / report projections
```

## Canonical input

`resources/institution/partnership.json` records the current formation facts, constitutional roles and responsibilities, authority principles, and institutional decisions. It deliberately contains null and unresolved states. The repository remains canonical; the Firm Console is read-only.

## Resolution semantics

Each decision distinguishes institutional state from legal state. This permits intent to be **RESOLVED** while legal implementation remains **COUNSEL_REVIEW**, or both institutional and legal work to remain **UNRESOLVED / NOT_YET_READY**.

`ResolvedPartnership` preserves the source facts, resolves person and office references, produces Partnership, Management, Responsibility, and Economics projections, validates totals and structural references, and reports gaps. Resolution must not create an answer merely to make a document complete.

## Initial outputs

The first executable output is the Resolved Partnership Manifest sent to the Firm Console. The console renders an HTML projection and reports. Future projections may include a Partnership Agreement draft, Decision Gap Report, Consistency Report, Counsel Review Report, Governance Manifest, and registration-oriented materials after counsel defines requirements.

No output may claim that an Agreement is legally valid. Generated prose must identify its source definition version and unresolved or counsel-review dependencies.
