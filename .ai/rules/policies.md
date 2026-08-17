---
paths:
  - 'app/Policies/**,resources/institution/policies.json,resources/js/pages/Policies/**'
---

# Policies

## Compile policy lifecycle from repository truth
Policy Markdown and resources/institution/policies.json are canonical; the Console is read-only. Never infer approval from Git, publication, use, or starter User authentication. Reviewed content is digest-locked, operative states require explicit approval evidence, and exceptions target an exact version with their own approval and expiry.
