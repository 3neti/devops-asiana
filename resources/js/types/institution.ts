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

export type AcceptanceReviewStatus =
    | 'identified'
    | 'under_review'
    | 'decision_recorded'
    | 'expired'
    | 'withdrawn';

export type AcceptanceOutcome =
    'accepted' | 'accepted_with_conditions' | 'rejected';

export type ClientAcceptanceDecision = {
    outcome: AcceptanceOutcome;
    outcome_label: string;
    reason: string;
    risk_classification: string | null;
    conditions: Array<Record<string, unknown>>;
    decision_maker: string;
    authority_basis: string;
    decided_at: string;
    valid_until: string | null;
    evidence_record_key: string;
    permits_engagement_consideration: boolean;
    temporal_state: 'within_validity' | 'past_validity';
};

export type ProspectiveClientProjection = {
    key: string;
    legal_name: string;
    display_name?: string;
    jurisdiction: string;
    entity_type: string;
    proposed_scope: string;
    review_status: AcceptanceReviewStatus;
    review_status_label: string;
    reviewers: string[];
    related_parties: Array<Record<string, unknown>>;
    assessments: Array<Record<string, unknown>>;
    decision: ClientAcceptanceDecision | null;
    institutional_status:
        'prospective_client' | 'accepted_client' | 'acceptance_expired';
};

export type ClientAcceptanceCompiler = {
    schema_version: number;
    compiler_status:
        'consistent' | 'consistent_with_gaps' | 'conflict_detected';
    governing_policy: {
        key: string;
        version: string;
        title: string;
        status: PolicyLifecycleStatus | 'invalid' | 'missing';
        status_label: string;
        operative: boolean;
    };
    required_assessments: Array<{
        key: string;
        label: string;
        question: string;
    }>;
    prospective_clients: ProspectiveClientProjection[];
    evidence_records: Array<Record<string, unknown>>;
    counts: {
        prospective_clients: number;
        evidence_records: number;
        by_review_status: Record<AcceptanceReviewStatus, number>;
        by_outcome: Record<AcceptanceOutcome, number>;
    };
    reports: {
        conflicts: Array<{ code: string; message: string }>;
        decision_gaps: Array<{ code: string; message: string }>;
        evidence_gaps: Array<{ code: string; message: string }>;
        readiness_gaps: Array<{ code: string; message: string }>;
    };
    principles: string[];
};

export type EngagementLifecycleStatus =
    | 'proposed'
    | 'under_review'
    | 'approved'
    | 'open'
    | 'suspended'
    | 'closed'
    | 'withdrawn';

export type EngagementProjection = {
    key: string;
    title: string;
    client_key: string;
    client_name: string | null;
    lifecycle_status: EngagementLifecycleStatus;
    lifecycle_status_label: string;
    operational_status:
        | 'pending'
        | 'approved_not_open'
        | 'open_engagement'
        | 'blocked_opening'
        | 'suspended'
        | 'closed';
    may_perform_client_work: boolean;
    responsible_partner: {
        partner_key: string;
        partner_name: string;
        effective_from: string;
        effective_until: string | null;
        evidence_record_key: string;
    } | null;
    scope?: {
        purpose: string;
        services: string[];
        deliverables: string[];
        exclusions: string[];
    };
};

export type EngagementCompiler = {
    schema_version: number;
    compiler_status:
        'consistent' | 'consistent_with_gaps' | 'conflict_detected';
    governing_policies: Array<{
        purpose: string;
        key: string;
        version: string;
        title: string;
        status: PolicyLifecycleStatus | 'missing';
        status_label: string;
        required_for_opening: boolean;
        operative: boolean;
    }>;
    opening_requirements: Array<{
        key: string;
        label: string;
        question: string;
    }>;
    engagements: EngagementProjection[];
    evidence_records: Array<Record<string, unknown>>;
    counts: {
        engagements: number;
        evidence_records: number;
        open_for_client_work: number;
        by_lifecycle_status: Record<EngagementLifecycleStatus, number>;
    };
    reports: {
        conflicts: Array<{ code: string; message: string }>;
        decision_gaps: Array<{ code: string; message: string }>;
        evidence_gaps: Array<{ code: string; message: string }>;
        readiness_gaps: Array<{ code: string; message: string }>;
    };
    principles: string[];
};

export type AccessGrantLifecycleStatus =
    | 'requested'
    | 'under_review'
    | 'approved'
    | 'provisioned'
    | 'active'
    | 'suspended'
    | 'expired'
    | 'revoked'
    | 'closed'
    | 'rejected'
    | 'withdrawn';

export type AccessGrantProjection = {
    key: string;
    title: string;
    lifecycle_status: AccessGrantLifecycleStatus;
    lifecycle_status_label: string;
    grant_type: 'standard' | 'privileged';
    grant_type_label: string;
    engagement_key: string;
    engagement_title: string | null;
    client_name: string | null;
    may_use_access: boolean;
    temporal_state: 'not_started' | 'within_validity' | 'past_expiry';
    operational_status:
        | 'active_authority'
        | 'blocked_active_grant'
        | 'approved_not_provisioned'
        | 'provisioned_not_active'
        | 'suspended'
        | 'expired'
        | 'revoked'
        | 'closed'
        | 'pending';
    actor?: {
        key: string;
        name: string;
        actor_type: 'person';
        account_type: 'named';
        firm_relationship: string;
    };
    scope?: {
        system: string;
        environment: string;
        account_identifier: string;
        permission_set: string[];
        purpose: string;
        client_mandate_action: string;
    };
};

export type ProductionAccessCompiler = {
    schema_version: number;
    compiler_status:
        'consistent' | 'consistent_with_gaps' | 'conflict_detected';
    governing_policies: Array<{
        purpose: string;
        key: string;
        version: string;
        required_for_activation: boolean;
        title: string;
        status: PolicyLifecycleStatus | 'missing';
        status_label: string;
        operative: boolean;
    }>;
    grant_requirements: Array<{
        key: string;
        label: string;
        question: string;
    }>;
    access_grants: AccessGrantProjection[];
    evidence_records: Array<Record<string, unknown>>;
    counts: {
        access_grants: number;
        evidence_records: number;
        active_authority: number;
        by_lifecycle_status: Record<AccessGrantLifecycleStatus, number>;
    };
    reports: {
        conflicts: Array<{ code: string; message: string }>;
        decision_gaps: Array<{ code: string; message: string }>;
        evidence_gaps: Array<{ code: string; message: string }>;
        readiness_gaps: Array<{ code: string; message: string }>;
    };
    principles: string[];
    boundary: string;
};

export type ChangeLifecycleStatus =
    | 'requested'
    | 'under_review'
    | 'approved'
    | 'scheduled'
    | 'executing'
    | 'verifying'
    | 'closed'
    | 'failed'
    | 'rolled_back'
    | 'cancelled'
    | 'rejected';

export type ChangeProjection = {
    key: string;
    title: string;
    lifecycle_status: ChangeLifecycleStatus;
    lifecycle_status_label: string;
    change_type: 'standard' | 'normal' | 'emergency';
    change_type_label: string;
    engagement_key: string;
    engagement_title: string | null;
    client_name: string | null;
    access_grant_key: string;
    access_grant_title: string | null;
    may_execute_change: boolean;
    window_state:
        'before_window' | 'within_window' | 'after_window' | 'undefined';
    operational_status:
        | 'authorized_for_execution'
        | 'blocked_scheduled_change'
        | 'approved_not_scheduled'
        | 'execution_in_progress'
        | 'awaiting_verification'
        | 'closed_verified'
        | 'failed'
        | 'rolled_back'
        | 'cancelled'
        | 'rejected'
        | 'pending';
    executor?: {
        key: string;
        name: string;
    };
    scope?: {
        system: string;
        environment: string;
        service: string;
        components: string[];
        expected_outcome: string;
    };
};

export type ChangeCompiler = {
    schema_version: number;
    compiler_status:
        'consistent' | 'consistent_with_gaps' | 'conflict_detected';
    governing_policies: Array<{
        purpose: string;
        key: string;
        version: string;
        required_for_execution: boolean;
        title: string;
        status: PolicyLifecycleStatus | 'missing';
        status_label: string;
        operative: boolean;
    }>;
    record_requirements: Array<{
        key: string;
        label: string;
        question: string;
    }>;
    change_records: ChangeProjection[];
    evidence_records: Array<Record<string, unknown>>;
    counts: {
        change_records: number;
        evidence_records: number;
        executable_authority: number;
        by_lifecycle_status: Record<ChangeLifecycleStatus, number>;
    };
    reports: {
        conflicts: Array<{ code: string; message: string }>;
        decision_gaps: Array<{ code: string; message: string }>;
        evidence_gaps: Array<{ code: string; message: string }>;
        readiness_gaps: Array<{ code: string; message: string }>;
    };
    principles: string[];
};
