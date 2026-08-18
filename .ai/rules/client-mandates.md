---
paths:
  - 'app/ClientMandates/**'
---

# Client Mandates

## Client actions require independent mandate and authority gates
ResolveClientMandates may emit a permitted action only when an Engagement is open for Client work, the Client Mandate covers the exact action/system/environment, the actor holds effective Firm Authority, separate Specific Approval exists, and Evidence is linked. Never infer Client authorization from Firm Authority, technical access, an open Engagement, or execution.
