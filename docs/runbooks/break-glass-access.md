# Runbook: Break-glass Access

**Governing policies:** Production Access; Authority and Delegation; Information Security; Incident Management

**Prerequisites:** Open Engagement; declared Incident; defined emergency where ordinary access delay increases material harm; named actor and account; bounded Client Mandate; required independent approvals; fixed automatic expiry; monitoring and evidence channels ready

1. Confirm the emergency condition, material harm from delay, Incident, Engagement, Client Mandate, named actor, minimum permissions, permitted actions, and prohibited actions.
2. Record Client emergency authority, Firm emergency authority, and independent security authority. The actor shall not approve their own access.
3. Establish the exact activation and expiry timestamps. Extension in place is prohibited; continued need requires a new record and approvals.
4. Retrieve the credential through the approved custody mechanism without copying secret material into the record. Verify MFA, named-session attribution, activity logging, and independent monitoring.
5. Activate only the approved permissions. Record activation actor, account, time, authority basis, verification, and evidence.
6. Record every material action, target, result, source, actor, and timestamp. The independent monitor shall escalate scope deviation and may require immediate termination.
7. At completion or expiry, end authority, remove permissions, rotate credentials where required, and obtain independent removal verification. Expiry ends authority even if technical cleanup is still underway.
8. Disclose purpose, material actions, result, and termination to the Client and Responsible Partner through the authorized communication path.
9. Conduct an independent blameless review of necessity, authority, scope adherence, activity, outcome, credential handling, and control performance. Assign corrective actions with owners and due dates.
10. Close only through a separate authorized, evidenced decision after removal, disclosure, review, and corrective-action accountability are complete.

**Completion:** emergency authority has ended; technical access removal is verified; required credential rotation and disclosure are recorded; review and corrective actions are accountable; closure is separately authorized and evidenced.
