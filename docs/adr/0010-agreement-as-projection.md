# ADR 0010: Treat the Partnership Agreement as a Projection

- **Status:** Accepted
- **Date:** 2026-08-17

## Context

A rendered Agreement can hide missing decisions behind plausible boilerplate and cannot safely serve as the only institutional model.

## Decision

Canonical Formation facts, constitutional rules, and decision states resolve into `ResolvedPartnership`. Agreement text and reports are projections of that intermediate object.

## Consequences

The compiler exposes uncertainty and counsel dependencies rather than inventing clauses. Generated output must retain source and resolution context and cannot claim legal validity.
