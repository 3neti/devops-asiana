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

## Deterministic Partnership Agreement projection

The repository now includes `CompilePartnershipAgreement`. It consumes the canonical `PartnershipDefinition` and its `ResolvedPartnership` intermediate representation to produce a deterministic working-draft Agreement. The projection includes resolved provisions, explicit decision gaps, counsel-review matters, conflicts, source and Agreement fingerprints, a compilation identifier, and a readable Markdown draft.

The compiler never calls an LLM, mutates canonical sources, advances institutional or legal status, or claims that the Agreement is valid, executed, or effective. Missing formation facts such as principal office, purpose, term, commencement, and formal loss treatment remain visible as `[UNRESOLVED]`; institutional intent whose legal mechanics remain unsettled is rendered as `[COUNSEL REVIEW]`.

The Firm Console presents this Agreement at the root of the Firm Map. Its Compile action is a read-only deterministic re-projection. The current canonical source state compiles as `WORKING DRAFT`.

## Initial outputs

The first executable output was the Resolved Partnership Manifest sent to the Firm Console. The current executable outputs also include the deterministic Partnership Agreement draft, Decision Gap Report, Consistency Report, Counsel Review Report, and Firm Map projections. Registration-oriented materials remain a later, counsel-defined projection.

No output may claim that an Agreement is legally valid. Generated prose must identify its source definition version and unresolved or counsel-review dependencies.
