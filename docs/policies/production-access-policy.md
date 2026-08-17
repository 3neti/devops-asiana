# Production Access Policy

| Field               | Initial value                                              |
| ------------------- | ---------------------------------------------------------- |
| Status              | Draft                                                      |
| Owner               | Reliability / SRE Practice Partner                         |
| Approving authority | Managing Partner / delegated security authority            |
| Version             | 0.1                                                        |
| Review frequency    | At least annually; access reviewed on a risk-based cadence |

## Principle

> **No Access Without Authority.**

Production access shall exist only for an approved Engagement, a legitimate task, and an authorized person. Technical possession of credentials is not authority to use them.

## Requirements

Production systems shall use named accounts, MFA, least privilege, environment separation, time-limited privilege where practical, approved credential storage, access logging, and prompt revocation. Shared root or administrator credentials are prohibited except for controlled break-glass mechanisms where the platform makes them unavoidable.

An Access Grant shall identify person, Client, Engagement, system and environment, role or permissions, purpose, approver and authority, start and expiry, authentication requirements, and evidence. Privileged access requires enhanced approval and should support independent authorization for high-risk actions.

The Firm shall conduct periodic access reviews and event-driven reviews after role change, Engagement change, incident, or departure. Dormant, excessive, orphaned, or unexplained access shall be removed or suspended pending review.

Break-glass use shall be limited to defined emergencies, logged, disclosed promptly, followed by credential rotation where appropriate, and independently reviewed. Client-owned credentials shall remain under Client ownership and custody arrangements defined by the Engagement.

Joiner, mover, and leaver procedures shall coordinate people records, Engagement assignments, devices, secrets, physical access, third-party accounts, and retained evidence.
