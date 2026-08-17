export type ResolutionState =
    'resolved' | 'unresolved' | 'counsel_review' | 'not_yet_ready';

export type FoundingPartner = {
    key: string;
    name: string;
    partner_status: string;
    governance_weight: number;
    economic_allocation: number;
    offices: string[];
    operational_posture: string;
    primary_responsibilities: string[];
    constitutional_rights: string[];
    practice_responsibilities: string[];
    engagement_responsibilities: string[];
};

export type ManagementOffice = {
    key: string;
    name: string;
    required: boolean;
    holder: string | null;
    holder_name: string | null;
    responsibilities: string[];
    authority_basis: string;
};

export type ResponsibilityAssignment = {
    key: string;
    label: string;
    holders: string[];
    holder_names: string[];
    status: 'assigned' | 'unassigned';
};

export type EconomicProjection = {
    basis: string;
    basis_definition: string;
    partner_allocations: Array<{
        key: string;
        name: string;
        percentage: number;
    }>;
    firm_allocation: {
        recipient_type: 'firm';
        label: string;
        percentage: number;
        purpose: string[];
    };
};

export type InstitutionalDecision = {
    key: string;
    label: string;
    institutional_state: ResolutionState;
    institutional_state_label: string;
    legal_state: ResolutionState;
    legal_state_label: string;
    statement: string;
};

export type ResolvedPartnership = {
    schema_version: number;
    compiler_status: 'consistent_with_open_decisions' | 'conflict_detected';
    formation: {
        firm: {
            name: string;
            jurisdiction: string;
            legal_form: string;
            legal_status: string;
            principal_office: string | null;
            effective_date: string | null;
        };
        founding_partners: FoundingPartner[];
        economics: Omit<EconomicProjection, 'partner_allocations'>;
    };
    constitution: {
        offices: Array<Omit<ManagementOffice, 'holder_name'>>;
        responsibility_assignments: Array<
            Omit<ResponsibilityAssignment, 'holder_names' | 'status'>
        >;
        reserved_matters: string[];
        authority_principles: Record<string, string>;
    };
    decisions: InstitutionalDecision[];
    projections: {
        partnership: FoundingPartner[];
        management: ManagementOffice[];
        responsibilities: ResponsibilityAssignment[];
        economics: EconomicProjection;
    };
    reports: {
        consistency: Array<{
            code: string;
            status: 'passed' | 'failed';
            message: string;
        }>;
        conflicts: Array<{ code: string; message: string }>;
        decision_gaps: InstitutionalDecision[];
        counsel_review: InstitutionalDecision[];
        responsibility_gaps: Array<{
            key: string;
            label: string;
            type: string;
        }>;
    };
    disclaimer: string;
};

export type InstitutionalNavigationGroup = {
    title: string;
    key: string;
    documents: Array<{
        key: string;
        title: string;
        href: string;
    }>;
};

export type PolicyLifecycleStatus =
    | 'draft'
    | 'under_review'
    | 'approved'
    | 'effective'
    | 'superseded'
    | 'retired';

export type PolicyVersion = {
    version: string;
    status: PolicyLifecycleStatus;
    status_label: string;
    document_path: string;
    content_digest: string;
    content_integrity:
        'mutable_draft' | 'verified' | 'digest_mismatch' | 'missing';
    review_frequency: string;
    effective_at: string | null;
    superseded_by: string | null;
    approval: null | {
        key: string;
        outcome: 'approved' | 'rejected';
        approver: string;
        authority_basis: string;
        decided_at: string;
        evidence_record_key: string;
    };
};

export type PolicyProjection = {
    key: string;
    title: string;
    owner: string;
    approving_authority: string;
    current_version: string;
    current_status: PolicyLifecycleStatus | 'invalid';
    current_status_label: string;
    current: PolicyVersion;
    versions: PolicyVersion[];
};

export type PolicyRegistry = {
    schema_version: number;
    compiler_status:
        'consistent' | 'consistent_with_gaps' | 'conflict_detected';
    policies: PolicyProjection[];
    exceptions: Array<Record<string, unknown>>;
    evidence_records: Array<Record<string, unknown>>;
    counts: {
        policies: number;
        exceptions: number;
        evidence_records: number;
        by_status: Record<PolicyLifecycleStatus, number>;
    };
    reports: {
        conflicts: Array<{ code: string; message: string }>;
        lifecycle_gaps: Array<{ code: string; message: string }>;
        evidence_gaps: Array<{ code: string; message: string }>;
    };
    principles: string[];
};
