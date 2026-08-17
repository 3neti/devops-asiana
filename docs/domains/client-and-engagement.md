# Client and Engagement Domain

A Prospective Client becomes a Client only through an explicit Client Acceptance decision. Acceptance answers whether the Firm may consider serving the organization; it does not authorize work.

The repository-backed Client Acceptance definition separates the Prospective Client, required assessments, related-party disclosures, risk observations, decision, decision-maker, authority basis, validity, conditions, and Evidence Records. Review activity never implies a decision. Conditional acceptance must state its conditions; rejection must state its reason; accepted status expires or requires re-review at a recorded time.

The governing Client Acceptance Policy must be Effective before the compiler will recognize an operative decision. This is a control-readiness requirement, not a claim that the current Draft policy has legal or institutional effect.

An Engagement is the controlling unit of material Client work. It defines commercial and operational scope, authority, systems, data, responsibilities, and evidence requirements. Exactly one Responsible Partner is assigned throughout the Engagement's active life, including a recorded handover if responsibility changes.

The Responsible Partner is the root of professional accountability, not necessarily the approver of every action. Security, Change, financial, or other authorities may approve within their domains, and authorized professionals may execute. The Responsible Partner ensures that the correct authority, staffing, escalation, and Client communication exist.

## Client Mandate

Client Mandate and Firm Authority are separate. A Client may authorize DevOps Asiana to deploy approved releases; the Firm may delegate normal deployment to an Engineer; and a Specific Approval may authorize one release. All three gates must hold. Technical access establishes none of them.

## Matter as an architectural candidate

A future `Matter` may distinguish bounded professional work—such as a database migration, DR exercise, security remediation, or Major Incident—from the broader Engagement. This could improve authority and evidence linkage. It is not yet an accepted domain requirement and should not be implemented until concrete workflows demonstrate the need.

Contribution attribution may identify Originating, Relationship, Responsible, and Engagement Partners, Technical Lead, Service Team, Practice, revenue, direct cost, contribution margin, and credits. Attribution never converts the Client relationship into personal property.

Key invariants for future software:

- work cannot become active without an accepted Client and approved Engagement;
- Client acceptance cannot be inferred from review activity, commercial discussion, an Engagement reference, access, or performed work;
- accepted or rejected decisions identify the decision-maker, authority basis, validity where applicable, and decision evidence;
- material conflicts and related-party relationships are disclosed and disposed rather than silently ignored;
- a material active Engagement has exactly one current Responsible Partner;
- responsibility history is preserved rather than overwritten;
- asset ownership is distinct from authority to operate;
- Client Mandate is distinct from Firm Authority and Specific Approval;
- access, Changes, Incidents, evidence, and commercial commitments link to an Engagement.
