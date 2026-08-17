# ADR 0008: Represent Policy Exceptions Explicitly

- **Status:** Accepted
- **Date:** 2026-08-17

## Context

Operational reality creates exceptions; invisible bypasses erode policy and auditability.

## Decision

An exception is a first-class, scoped, risk-assessed, approved, compensating, expiring, and reviewable record tied to an exact policy requirement.

## Consequences

Free text and execution do not create exceptions. Repeated or expired exceptions become visible and can trigger remediation or policy review.
