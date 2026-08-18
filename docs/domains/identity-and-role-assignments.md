# Institutional Identity and Role Assignments

## Purpose

This domain records who the Firm recognizes, which institutional Roles they hold, when those assignments operate, and what source supports them. It prevents names, titles, application accounts, technical access, and observed behavior from becoming silent substitutes for authority.

The canonical registry is `resources/institution/identity-and-roles.json`. The registry is an institutional definition, not an HRIS, user directory, or credential store.

## Separate concepts

The Firm shall preserve these distinctions:

| Concept | Meaning |
| --- | --- |
| Institutional Identity | Stable reference to a known person or other future recognized subject |
| Partner Status | Constitutional relationship created under the Partnership Agreement |
| Employment Relationship | Legal or service classification requiring its own decision and counsel review |
| Application Authentication | Technical ability to sign in to a system |
| System Account | Technical identity in a Client or Firm-controlled system |
| Role | Named office, professional responsibility, or delegated-authority function |
| Role Assignment | Historical record connecting an Identity to a Role for a defined period and basis |
| Firm Authority | Institutional permission arising from a valid authority source, never merely from identity or access |

An authenticated `User` is not automatically an Institutional Identity. An Institutional Identity is not automatically an employee, Partner, account holder, Role holder, or authorized actor.

## Role categories

### Office

An Office is a transferable institutional position, such as Managing Partner. Responsibilities and any ordinary authority attach to the Office. They move only through a valid appointment; they are not rewritten around the current holder.

### Professional responsibility

A professional-responsibility Role identifies accountable stewardship of work or capability. It does not by itself authorize the holder to bind the Firm, approve a high-risk action, or exercise technical access.

### Delegated authority

A delegated-authority Role may carry Firm Authority only when the underlying delegation is explicit, approved, evidenced, bounded by domain and action, time-limited, and not prohibited from subdelegation by its terms. The Role name alone carries no authority.

Personal constitutional rights remain attached to Partner status under the constitution. They are not represented as transferable Role Assignments.

## Assignment lifecycle

```text
Proposed → Approved → Active → Suspended → Active → Ended
                                      └────────────→ Revoked
```

Lifecycle state, effective time, and institutional validity are separate facts.

- **Proposed** records a candidate assignment without approval.
- **Approved** records an authorized assignment that has not yet been activated.
- **Active** asserts current operation, subject to all source, qualification, time, evidence, and delegation requirements.
- **Suspended** preserves the assignment while disabling its operation.
- **Ended** records ordinary completion with attributable disposition.
- **Revoked** records withdrawal by competent authority with attributable disposition.

Approval shall not be inferred from activation, execution, technical access, a title, or a prior act. Ending or revoking one assignment shall not erase it or silently transfer its responsibilities to another person.

## Activation conditions

An assignment is operative only when all applicable conditions are satisfied:

1. the Identity and Role are known;
2. the subject satisfies the Role qualification;
3. the institutional basis is explicit;
4. required approval is separately recorded;
5. the effective time has arrived and expiry has not passed;
6. the lifecycle is Active;
7. required Evidence is linked and complete; and
8. delegated authority, when applicable, has bounded scope and expiry.

Only an operative Office or delegated-authority assignment may contribute Firm Authority. A professional-responsibility assignment can be operative without granting Firm Authority.

### Founding assignment assumption

Formation-derived assignments have an additional admission boundary. Verified Firm Commencement makes an approved assignment eligible, but does not activate it. The holder must accept one exact assignment, another recognized person must independently verify that acceptance and the assignment snapshot, and the activation itself must be separately recorded. Acceptance, verification, and activation each require distinct Evidence.

`resources/institution/role-activations.json` is the canonical assumption register. `ResolveRoleActivations` may emit an activation admission only for an exact approved formation assignment whose Role, holder, formation reference, Commencement basis, chronology, and Evidence reconcile. One admission activates only its named assignment. It does not activate the holder's other responsibilities and declares no Firm Authority.

`ResolveIdentityAndRoles` preserves the canonical assignment lifecycle as Approved while projecting an admitted assignment's effective lifecycle as Active. This prevents the activation projection from rewriting historical institutional truth. A manually declared Active state cannot substitute for an admitted assumption.

## Founding state

The registry recognizes Lester B. Hurtado and Angelica Anaïs C. Santos by reference to the Partnership Formation. It defines nine Roles and records eight formation-derived assignments. Security & Compliance and Privileged Emergency Access Approver remain unassigned.

The assignments are Approved but not Active. Their eligibility may be supplied only by a verified Firm Commencement basis, not by a date standing alone, and each exact assignment then requires a separately admitted holder assumption. The principal office, Firm effective date, counsel-confirmed legal requirement set, capital initialization, Commencement Record, assumption records, and Evidence remain unresolved. The compilers therefore report eight pending assumptions, seven assigned Roles pending activation, two Roles vacant, and zero effective Firm Authority. They do not invent commencement or treat formation records as already operative.

Employment or service classification for both founders remains unresolved and separate from their Founding Partner status. Neither Institutional Identity is bound to an application login or system account in the canonical registry.

## Reconciliation and history

Role holders are reconciled against the Responsibility Coverage Compiler. A mismatch is a conflict rather than an invitation to choose one record silently. Exclusive Offices cannot have overlapping current assignments.

Active assignment and disposition records require Evidence. Appointment, suspension, ending, and revocation should eventually remain append-only institutional history. Database persistence is deferred until lifecycle and authority requirements justify it.

## Next boundary

A future Role Assignment Transition Compiler should preserve suspension, resignation, removal, revocation, ending, successor appointment, and resulting responsibility coverage as distinct attributable events. Ending one assignment must create a visible vacancy when no independently admitted successor exists; it must never transfer a Role or authority silently.
