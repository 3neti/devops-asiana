# ADR 0011: Keep the Firm Console Repository-Backed and Read-Only

- **Status:** Accepted
- **Date:** 2026-08-17

## Context

The repository is the reviewed institutional source, and the Firm does not yet need a document editor or persistence layer.

## Decision

The Firm Console reads canonical partnership data and Markdown from the repository, renders sanitized projections, and provides no editing path.

## Consequences

Agents and people change truth through source control. The browser supports inspection and compilation without becoming a parallel source or generic admin system.
