---
paths:
  - 'app/ProductionAccess/**,resources/institution/production-access.json,resources/js/pages/ProductionAccess/**'
---

# Production Access

## Keep Secrets and Break-glass Outside Ordinary Grants
Canonical Production Access records contain credential ownership, custody, vault references, rotation, and evidence, but never passwords, tokens, private keys, recovery codes, or credential values. Break-glass access is a separate emergency path and must not be represented as a standard or privileged ordinary Access Grant.
