# Evidence Model

An **Evidence Record** is a durable index entry proving or supporting a material institutional fact. The underlying item may be structured data, a signed document, log, approval response, exported report, image, recording where lawful, provider reference, or checksum-addressed file.

## Minimum attributes

- stable identifier and record type;
- subject and links to Client, Engagement, policy, procedure, or operational record;
- actor or originating system;
- event and capture timestamps;
- source and custody location;
- reason or asserted fact;
- approval and authority references where applicable;
- lifecycle state and integrity metadata;
- confidentiality, ownership, retention, and legal-hold classification;
- relationships to superseded, corrective, or contradictory evidence.

Evidence should be append-only after finalization. Correction creates a new attributable entry and preserves the original. Storage controls should make unauthorized alteration detectable without premature blockchain or distributed-ledger complexity.

## Important separations

Proposal, review, approval, execution, verification, and closure are distinct events. The presence of deployment logs proves execution, not approval. An approval proves authorization, not successful execution. Verification proves a defined observation, not universal correctness.

The model must support the interrogation chain:

```text
Authority → Approval → Action → Verification → Evidence → Follow-up
```
