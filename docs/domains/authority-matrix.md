# Authority Matrix

## Purpose

The Authority Matrix states who may decide, approve, commit, or act for the Firm, for which institutional action, under which source and boundary. It is a controlled projection of the Partnership Constitution, operative policy, Responsibility Coverage, Institutional Identities, and Role Assignments.

The canonical definition is `resources/institution/authority-matrix.json`. It is not an application permission table and does not authorize Laravel users, system accounts, or technical credentials.

## Three independent gates

```text
Firm Authority
      +
Client Mandate
      +
Specific Approval
      =
Permitted Client Action
```

The Matrix resolves only Firm Authority.

- **Firm Authority** identifies the person who may decide or approve for the Firm.
- **Client Mandate** identifies what the Client authorized the Firm to do under an Engagement or valid instruction.
- **Specific Approval** authorizes one bounded action within both authorities.

Possessing one or two gates does not imply the third. Even an effective Matrix entry never sets `authorizes_client_action` to true.

## Authority sources

An entry shall use one explicit source:

- **Personal constitutional status**, for rights that belong to a Partner personally under the constitution;
- **Office**, where authority follows a transferable institutional Office and its operative appointment; or
- **Delegation**, where an authorized source grants bounded, evidenced, and time-limited authority to an eligible Role holder.

Professional responsibility, Partner ownership, governance weight, economic allocation, title, authentication, system access, past practice, and execution do not constitute an authority source.

## Matrix entry

Each entry identifies:

- domain and action;
- decision stage;
- exact Responsibility Coverage requirement;
- constitutional or exact policy-version source;
- holder-resolution rule;
- Firm-only authority boundary;
- Client Mandate and Specific Approval gates;
- risk and threshold boundary;
- exclusions;
- separation and verification requirements;
- delegation and subdelegation limits;
- lifecycle, effective period, and expiry; and
- Evidence Record.

Monetary thresholds shall be either resolved with a value, explicitly not applicable, or unresolved. An unresolved threshold blocks authority. The compiler never supplies an illustrative value as an operative threshold.

## Lifecycle

```text
Design → Approved → Active → Superseded
                         └──→ Retired
```

- **Design** describes a future control and creates no authority.
- **Approved** preserves institutional approval but remains non-operative.
- **Active** asserts current authority and must satisfy every source, holder, time, policy, boundary, and evidence condition.
- **Superseded** preserves a replaced entry as history.
- **Retired** records an entry that no longer applies.

Approval never implies activation. Activation never supplies Client Mandate or Specific Approval. Execution never proves approval.

## Effective authority

An Active entry grants Firm Authority only when:

1. its action and Responsibility Coverage requirement are known;
2. its authority source exactly matches the requirement source;
3. the governing Authority and Delegation Policy is Effective;
4. a policy-derived source is the exact Effective version;
5. its effective time has arrived and it has not expired;
6. its holder resolves through recognized constitutional status or an operative authority-bearing Role Assignment;
7. its scope, thresholds, separation, and delegation bounds are complete; and
8. a complete Evidence Record is linked.

An Office assignment or delegated Role Assignment must itself be operative before the Matrix may use it. A professional-responsibility assignment is never an authority-bearing substitute.

## Initial Matrix

The first Matrix defines only seven grounded actions:

1. Founding Partner participation in Reserved Matter decisions;
2. Managing Partner exercise of ordinary Firm management;
3. privileged emergency-access approval;
4. production-access approval;
5. high-risk production Change approval;
6. Client Incident disclosure approval; and
7. continuity-exercise approval.

The first three are constitution-derived Approved entries. Reserved Matter participation and ordinary management await the unresolved Firm effective date and operative assignments. Privileged emergency-access authority also has no holder. The four policy-derived entries remain Design because their source policies are Draft.

Accordingly, the current Matrix grants zero effective Firm Authority.

## Explicitly deferred authority

No authority is implied for:

- commercial commitments or contract signature;
- expenditure, procurement, banking, discounts, or write-offs;
- hiring, termination, or compensation commitments;
- borrowing or guarantees;
- litigation or settlement;
- credential issuance or data export; or
- public or regulatory communication.

These are first-class deferred decisions. Borrowing, guarantees, litigation, Partner power to bind the General Partnership, and related limits require Philippine counsel where identified. They shall not be introduced until the Firm approves the relevant classifications, roles, thresholds, escalation, and evidence requirements.

## Next boundary

A future Decision and Approval Record may cite one exact Active Matrix entry, the resolved holder, proposal, risk, outcome, time, and evidence. That record must preserve approval separately from execution and must not become a generalized workflow engine.
