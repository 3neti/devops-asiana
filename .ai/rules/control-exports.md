---
paths:
  - 'app/ControlExports/**'
---

# Control Exports

## Control review exports are stable and payload-free
ResolveControlReviewEvidenceExport projects only the Institutional Control Review using an explicit source identity and field allowlist. Gap category, code, message, and control provenance remain visible; payloads and secrets are forbidden. Exporting never grants authority, accepts risk, creates exceptions, or closes remediation.
