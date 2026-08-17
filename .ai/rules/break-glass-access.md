---
paths:
  - 'app/BreakGlassAccess/**,resources/institution/break-glass-access.json,resources/js/pages/BreakGlassAccess/**,tests/**/*BreakGlass*'
---

# Break Glass Access

## Keep Break-glass separate and self-expiring
Break-glass is a separate emergency authority path, never an ordinary Access Grant. Require a declared Incident, Open Engagement, named actor/account, bounded Client Mandate and scope, three independent approvals, logging, monitoring, and an absolute non-renewable expiry. Credential possession never creates authority; the actor cannot self-approve, self-monitor, or self-review. Expiry ends authority, while removal, disclosure, review, corrective action, and closure remain separate evidenced facts.
