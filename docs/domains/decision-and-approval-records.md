# Decision and Approval Records

## Purpose

The Decision Record is the Firm's durable answer to: what was proposed, who reviewed it, what risk was identified, who possessed authority, what was decided, when it became effective, what was later executed, who verified execution, and where the Evidence resides.

This compiler initially covers Firm governance and Firm management only. It does not authorize Client actions or replace Client Mandate, Engagement authority, or action-specific operational records.

## Institutional chain

```text
Proposal
    + Review
    + Risk classification and acceptance where required
    + exact effective Authority Matrix entry and holder
        ↓
Explicit Decision
        ↓
Effective period and conditions
        ↓
Separately recorded Execution
        ↓
Independent Verification
        ↓
Evidence at every material stage
```

Execution cannot cure a missing or invalid decision. Verification cannot rewrite an approval or prove that authority existed. An approved decision may permit execution without asserting that execution occurred.

## Record boundary

A Decision Record shall identify one context (`firm_governance` or `firm_management`), subject, materiality, proposal, review, risk, authority citation, outcome, effective period, conditions, and Evidence. An operative outcome must cite exactly one effective Authority Matrix entry and one of its effective holders.

Proposal, review, risk acceptance, decision, execution, and verification evidence are separate records. Material and Reserved decisions require independence between proposal and review. Self-approval is prohibited whenever the cited Matrix entry prohibits it. Execution and verification are stored independently and preserve their own actor, time, result, and Evidence.

## Truthful initial state

The canonical ledger contains no records. Known formation facts are not reconstructed as historical decisions. The Partnership Governance Policy and Authority & Delegation Policy remain Draft, and the Authority Matrix has no effective entries. The compiler therefore reports three readiness gaps and zero executable decisions.

## Explicit exclusions

This slice is not a workflow engine, meeting manager, voting system, document editor, or source of Client Mandate. It does not resolve Reserved Matter quorum, votes, abstentions, recusals, or 50/50 deadlock. Those governance mechanics remain a distinct future projection and subject to unresolved constitutional decisions and counsel review.
