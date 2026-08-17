# Change Management Policy

| Field               | Initial value                                        |
| ------------------- | ---------------------------------------------------- |
| Status              | Draft                                                |
| Owner               | Reliability / SRE Practice Partner                   |
| Approving authority | Managing Partner / delegated operational authority   |
| Version             | 0.1                                                  |
| Review frequency    | At least annually and after a material failed change |

## Principles

> **No Ticket, No Change. No Production Change Without Recovery.**

Every production Change shall have a durable record before execution, except that an emergency record may be created contemporaneously when delay would materially increase harm.

## Change record

The record shall contain request and rationale; Client and Engagement; affected services and assets; risk classification; technical review; required Client and Firm approval; implementation plan; recovery or rollback plan; backup or recoverability confirmation; schedule; executor; communications; deployment evidence; verification; outcome; exceptions; and closure.

Standard Changes are pre-authorized, repeatable, low-risk procedures with defined eligibility and periodic review. Normal Changes receive risk-proportionate review and approval. Emergency Changes use expedited authority to contain or prevent material harm, but require disclosure and retrospective review. Classification shall not be used to avoid scrutiny.

The executor shall confirm authority and recovery readiness before production action. Failed or partially successful Changes shall be stabilized, rolled back or otherwise recovered, communicated, and reviewed according to impact. Verification is separate from deployment and should be performed independently for high-risk Changes where practical.

Material Changes require post-implementation review when risk, failure, Client impact, or policy specifies it. Closure occurs only when verification and required evidence are complete and follow-up actions have owners.
