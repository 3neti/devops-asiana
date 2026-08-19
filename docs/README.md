# Institutional Manual

This directory is the controlled institutional specification for DevOps Asiana. It describes the Firm that the application must eventually enforce.

## Authority chain

1. The future Partnership Agreement constitutes the Firm and delegates policy-making authority.
2. Policies state mandatory outcomes, ownership, and control requirements.
3. Procedures describe repeatable business processes; runbooks describe operational response and execution.
4. Evidence records prove decisions, approvals, actions, verification, and closure.

If documents conflict, the higher layer governs unless an authorized, recorded, time-bound exception applies. No document here substitutes for executed legal instruments or advice from Philippine counsel.

## Document conventions

Policies use **shall** for requirements and **should** for preferred practice. Draft policy metadata identifies owner, approver, lifecycle state, effective date, review interval, and supersession. Initial policies are institutional drafts: they are not effective merely because they are committed to Git.

Procedures and runbooks must name their governing policy, authorized roles, required approvals, required evidence, escalation path, and completion condition. Records must link back to the authority under which they were created.

## Navigation

| Area            | Purpose                                                  |
| --------------- | -------------------------------------------------------- |
| `vision/`       | Purpose, operating philosophy, ecosystem position        |
| `constitution/` | Counsel-ready requirements for the Partnership Agreement |
| `policies/`     | Mandatory institutional controls                         |
| `procedures/`   | Repeatable administrative and control processes          |
| `runbooks/`     | Operational execution and emergency response             |
| `evidence/`     | Evidence model, retention concepts, record catalogue     |
| `domains/`      | Ubiquitous language and domain boundaries                |
| `architecture/` | Software boundary and data design direction              |
| `adr/`          | Durable institutional and technical decisions            |
| `legal/`        | Matters requiring counsel validation                     |

The canonical [Compass](COMPASS.md) records the current epoch, institutional gates, active frontier, and explicit build restraint for future agents.

The executable projections are summarized under [Future operating system](architecture/future-operating-system.md). The [Formation Completion and Firm Commencement doctrine](domains/formation-completion-and-firm-commencement.md) defines the newest compiler boundary. The browser catalogue is generated from this hierarchy; the repository remains canonical.
