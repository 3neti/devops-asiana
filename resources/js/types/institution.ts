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

export type IncidentLifecycleStatus =
    | 'detected'
    | 'triaged'
    | 'declared'
    | 'active'
    | 'contained'
    | 'recovering'
    | 'monitoring'
    | 'service_restored'
    | 'under_review'
    | 'closed'
    | 'false_positive';

export type IncidentProjection = {
    key: string;
    title: string;
    lifecycle_status: IncidentLifecycleStatus;
    lifecycle_status_label: string;
    incident_type: 'operational' | 'security';
    incident_type_label: string;
    severity: string;
    major_incident: boolean;
    engagement_key: string;
    engagement_title: string | null;
    client_name: string | null;
    responsible_partner_name: string | null;
    response_policies_operative: boolean;
    review_required: boolean;
    active_response: boolean;
    service_restored: boolean;
    may_close_incident: boolean;
    operational_status:
        | 'closed_verified'
        | 'blocked_closure'
        | 'ready_for_closure'
        | 'restored_not_closed'
        | 'false_positive'
        | 'active_response'
        | 'pending';
};

export type IncidentCompiler = {
    schema_version: number;
    compiler_status:
        'consistent' | 'consistent_with_gaps' | 'conflict_detected';
    governing_policies: Array<{
        purpose: string;
        key: string;
        version: string;
        applies_to: 'all' | 'security' | 'major';
        required_for_declaration: boolean;
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
    incident_records: IncidentProjection[];
    evidence_records: Array<Record<string, unknown>>;
    counts: {
        incident_records: number;
        evidence_records: number;
        active_response: number;
        awaiting_closure: number;
        by_lifecycle_status: Record<IncidentLifecycleStatus, number>;
    };
    reports: {
        conflicts: Array<{ code: string; message: string }>;
        decision_gaps: Array<{ code: string; message: string }>;
        evidence_gaps: Array<{ code: string; message: string }>;
        readiness_gaps: Array<{ code: string; message: string }>;
    };
    principles: string[];
};

export type BreakGlassLifecycleStatus =
    | 'requested'
    | 'authorized'
    | 'activated'
    | 'expired'
    | 'revoked'
    | 'under_review'
    | 'closed'
    | 'rejected'
    | 'cancelled';

export type BreakGlassAccessProjection = {
    key: string;
    title: string;
    lifecycle_status: BreakGlassLifecycleStatus;
    lifecycle_status_label: string;
    engagement_key: string;
    engagement_title: string | null;
    client_name: string | null;
    incident_key: string;
    incident_title: string | null;
    window_state: 'undefined' | 'before_window' | 'active' | 'expired';
    may_use_break_glass: boolean;
    operational_status:
        | 'closed_verified'
        | 'blocked_closure'
        | 'expired_authority'
        | 'active_emergency_authority'
        | 'awaiting_review'
        | 'ready_for_closure'
        | 'rejected'
        | 'cancelled'
        | 'pending';
    actor?: {
        key: string;
        name: string;
        account_identifier: string;
        account_type: 'named';
    };
    scope?: {
        system: string;
        environment: string;
        permissions: string[];
        purpose: string;
        permitted_actions: string[];
        prohibited_actions: string[];
    };
};

export type BreakGlassAccessCompiler = {
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
    record_requirements: Array<{
        key: string;
        label: string;
        question: string;
    }>;
    access_records: BreakGlassAccessProjection[];
    evidence_records: Array<Record<string, unknown>>;
    counts: {
        access_records: number;
        evidence_records: number;
        active_emergency_authority: number;
        awaiting_review: number;
        by_lifecycle_status: Record<BreakGlassLifecycleStatus, number>;
    };
    reports: {
        conflicts: Array<{ code: string; message: string }>;
        decision_gaps: Array<{ code: string; message: string }>;
        evidence_gaps: Array<{ code: string; message: string }>;
        readiness_gaps: Array<{ code: string; message: string }>;
    };
    principles: string[];
    prohibited_content: string;
};

export type CorrectiveActionLifecycleStatus =
    | 'proposed'
    | 'assigned'
    | 'in_progress'
    | 'pending_verification'
    | 'verified'
    | 'closed'
    | 'cancelled'
    | 'superseded';

export type CorrectiveActionProjection = {
    key: string;
    title: string;
    lifecycle_status: CorrectiveActionLifecycleStatus;
    lifecycle_status_label: string;
    source_type_label: string;
    source_resolved: boolean;
    governing_policy_operative: boolean;
    current_due_at: string | null;
    overdue: boolean;
    escalation_current: boolean;
    may_close_corrective_action: boolean;
    operational_status:
        | 'proposed'
        | 'assigned'
        | 'active'
        | 'overdue'
        | 'awaiting_verification'
        | 'ready_for_closure'
        | 'closed_verified'
        | 'blocked_closure'
        | 'cancelled'
        | 'superseded';
    source?: {
        type: string;
        key: string;
        finding: string;
    };
    owner?: {
        key: string;
        name: string;
        role: string;
    };
};

export type CorrectiveActionCompiler = {
    schema_version: number;
    compiler_status:
        'consistent' | 'consistent_with_gaps' | 'conflict_detected';
    governing_policies: Array<{
        purpose: string;
        key: string;
        version: string;
        applies_to: string[];
        required_for_assignment: boolean;
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
    corrective_actions: CorrectiveActionProjection[];
    evidence_records: Array<Record<string, unknown>>;
    counts: {
        corrective_actions: number;
        evidence_records: number;
        overdue: number;
        awaiting_verification: number;
        ready_for_closure: number;
        by_lifecycle_status: Record<CorrectiveActionLifecycleStatus, number>;
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

export type ContinuityExerciseLifecycleStatus =
    | 'proposed'
    | 'approved'
    | 'scheduled'
    | 'in_progress'
    | 'awaiting_verification'
    | 'verified'
    | 'closed'
    | 'cancelled';

export type ContinuityExerciseProjection = {
    key: string;
    title: string;
    lifecycle_status: ContinuityExerciseLifecycleStatus;
    lifecycle_status_label: string;
    exercise_type: 'tabletop' | 'backup_restore' | 'failover' | 'full_scale';
    exercise_type_label: string;
    window_state:
        'undefined' | 'before_window' | 'within_window' | 'after_window';
    may_execute_exercise: boolean;
    may_close_exercise: boolean;
    objectives_met: number;
    objectives_missed: number;
    objectives_not_measured: number;
    unresolved_gaps: number;
    operational_status:
        | 'proposed'
        | 'approved_not_scheduled'
        | 'authorized_for_execution'
        | 'blocked_scheduled_exercise'
        | 'exercise_in_progress'
        | 'awaiting_verification'
        | 'ready_for_closure'
        | 'verified_with_open_gaps'
        | 'closed_verified'
        | 'blocked_closure'
        | 'cancelled';
};

export type ContinuityExerciseCompiler = {
    schema_version: number;
    compiler_status:
        'consistent' | 'consistent_with_gaps' | 'conflict_detected';
    governing_policies: Array<{
        purpose: string;
        key: string;
        version: string;
        required_for_approval: boolean;
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
    exercise_records: ContinuityExerciseProjection[];
    evidence_records: Array<Record<string, unknown>>;
    counts: {
        exercise_records: number;
        evidence_records: number;
        objectives_missed: number;
        unresolved_gaps: number;
        ready_for_closure: number;
        by_lifecycle_status: Record<ContinuityExerciseLifecycleStatus, number>;
    };
    reports: {
        conflicts: Array<{ code: string; message: string }>;
        decision_gaps: Array<{ code: string; message: string }>;
        evidence_gaps: Array<{ code: string; message: string }>;
        readiness_gaps: Array<{ code: string; message: string }>;
    };
    principles: string[];
};

export type ResponsibilityCoverageStatus =
    'covered' | 'vacant' | 'pending_activation' | 'conflicted';

export type ResponsibilityRequirement = {
    key: string;
    label: string;
    category: 'office' | 'responsibility' | 'authority';
    criticality: 'constitutional' | 'material' | 'important';
    source: {
        type: 'constitution' | 'policy';
        reference?: string;
        key?: string;
        version?: string;
    };
    source_status: 'operative' | PolicyLifecycleStatus | 'missing' | 'invalid';
    source_label: string;
    holder_source: {
        type: 'office' | 'responsibility' | 'unassigned';
        key?: string;
    };
    authority_attachment:
        | 'office'
        | 'partner_status'
        | 'professional_role'
        | 'delegation'
        | 'none';
    required_holders: {
        minimum: number;
        maximum: number | null;
    };
    qualified_partner_statuses: string[];
    responsibilities: string[];
    concentration_review: boolean;
    succession: {
        required: boolean;
        alternate_holder_keys: string[];
    };
    holder_keys: string[];
    holder_names: string[];
    alternate_holder_keys: string[];
    alternate_holder_names: string[];
    coverage_status: ResponsibilityCoverageStatus;
    sole_holder: boolean;
};

export type ResponsibilityCoverageCompiler = {
    schema_version: number;
    compiler_status:
        'consistent' | 'consistent_with_gaps' | 'conflict_detected';
    concentration_review_threshold: number;
    requirements: ResponsibilityRequirement[];
    separation_constraints: Array<{
        key: string;
        left_requirement_key: string;
        right_requirement_key: string;
        reason: string;
        status: 'satisfied' | 'violated' | 'pending_activation' | 'invalid';
        overlapping_holder_keys: string[];
    }>;
    counts: {
        requirements: number;
        covered: number;
        vacant: number;
        pending_activation: number;
        conflicted: number;
        succession_gaps: number;
        concentration_exposures: number;
    };
    reports: {
        conflicts: Array<{ code: string; message: string }>;
        vacancies: Array<{
            code: string;
            requirement_key: string;
            message: string;
        }>;
        qualification_gaps: Array<{
            code: string;
            requirement_key: string;
            message: string;
        }>;
        succession_gaps: Array<{
            code: string;
            requirement_key: string;
            message: string;
        }>;
        concentration_exposures: Array<{
            code: string;
            holder_key: string;
            holder_name: string;
            requirement_keys: string[];
            message: string;
        }>;
        separation_conflicts: Array<{
            code: string;
            constraint_key: string;
            holder_keys: string[];
            message: string;
        }>;
        pending_requirements: Array<{
            code: string;
            requirement_key: string;
            message: string;
        }>;
    };
    principles: string[];
};

export type AssignmentLifecycleStatus =
    'proposed' | 'approved' | 'active' | 'suspended' | 'ended' | 'revoked';

export type InstitutionalIdentity = {
    key: string;
    subject_type: 'person';
    partnership_person_key: string;
    lifecycle_status: 'recognized' | 'inactive' | 'archived';
    display_name: string;
    partner_status: string | null;
    authentication_binding: Record<string, unknown> | null;
    authentication_bound: boolean;
    employment_relationship: {
        state: 'resolved' | 'unresolved';
        classification: string | null;
    };
    system_account_keys: string[];
    institutional_status: string;
};

export type InstitutionalRole = {
    key: string;
    name: string;
    category: 'office' | 'professional_responsibility' | 'delegated_authority';
    authority_attachment:
        'office' | 'professional_role' | 'delegation' | 'none';
    responsibility_requirement_key: string;
    exclusive: boolean;
    qualified_partner_statuses: string[];
    responsibilities: string[];
    expected_holder_keys: string[];
    recorded_holder_keys: string[];
    recorded_holder_names: string[];
    coverage_status: ResponsibilityCoverageStatus;
    operative_assignment_count: number;
};

export type RoleAssignment = {
    key: string;
    role_key: string;
    role_name: string;
    identity_key: string;
    identity_name: string;
    lifecycle_status: AssignmentLifecycleStatus;
    lifecycle_status_label: string;
    basis: {
        type: 'formation' | 'appointment' | 'delegation';
        reference: string;
    };
    effective_at: string | null;
    effective_at_source: string | null;
    effective_at_resolved: string | null;
    expires_at: string | null;
    operative: boolean;
    grants_firm_authority: boolean;
    temporal_state: string;
    operational_status: string;
};

export type IdentityAndRoleCompiler = {
    schema_version: number;
    compiler_status:
        'consistent' | 'consistent_with_gaps' | 'conflict_detected';
    identities: InstitutionalIdentity[];
    roles: InstitutionalRole[];
    assignments: RoleAssignment[];
    evidence_records: Array<Record<string, unknown>>;
    counts: {
        identities: number;
        roles: number;
        assignments: number;
        authority_effective: number;
        authentication_bindings: number;
        by_assignment_lifecycle: Record<AssignmentLifecycleStatus, number>;
        by_role_coverage: Record<ResponsibilityCoverageStatus, number>;
    };
    reports: {
        conflicts: Array<{ code: string; message: string }>;
        identity_gaps: Array<{ code: string; message: string }>;
        activation_gaps: Array<{ code: string; message: string }>;
        evidence_gaps: Array<{ code: string; message: string }>;
        holder_mismatches: Array<{ code: string; message: string }>;
    };
    principles: string[];
};

export type AuthorityEntryLifecycleStatus =
    'design' | 'approved' | 'active' | 'superseded' | 'retired';

export type AuthorityResolutionStatus =
    | 'effective'
    | 'design_only'
    | 'vacant_holder'
    | 'pending_activation'
    | 'blocked'
    | 'conflicted';

export type AuthorityMatrixEntry = {
    key: string;
    domain_key: string;
    domain_label: string;
    action_key: string;
    action_label: string;
    action_stage: 'decision' | 'approval' | 'execution' | 'verification';
    responsibility_requirement_key: string;
    authority_source: Record<string, string>;
    source_label: string;
    source_operative: boolean;
    holder_rule: {
        type: 'partner_status' | 'role' | 'requirement_holders';
        key: string;
    };
    scope: {
        authority_boundary: 'firm_authority_only';
        client_mandate_required: boolean;
        specific_approval_required: boolean;
        risk_boundary: string;
        thresholds: {
            monetary_status: 'not_applicable' | 'resolved' | 'unresolved';
            monetary_limit: number | null;
            risk_levels: string[];
        };
        exclusions: string[];
    };
    separation: {
        self_approval_permitted: boolean;
        execution_separate: boolean;
        independent_verification_required: boolean;
    };
    delegation: {
        permitted: boolean;
        subdelegation_permitted: boolean;
        requires_explicit_assignment: boolean;
        maximum_duration_days: number | null;
    };
    lifecycle_status: AuthorityEntryLifecycleStatus;
    lifecycle_status_label: string;
    effective_at_resolved: string | null;
    temporal_state: string;
    candidate_holder_keys: string[];
    candidate_holder_names: string[];
    effective_holder_keys: string[];
    effective_holder_names: string[];
    resolution_status: AuthorityResolutionStatus;
    grants_firm_authority: boolean;
    authorizes_client_action: false;
    client_mandate_gate: 'required_separately' | 'not_applicable';
    specific_approval_gate: 'required_separately' | 'not_applicable';
};

export type AuthorityMatrixCompiler = {
    schema_version: number;
    compiler_status:
        'consistent' | 'consistent_with_gaps' | 'conflict_detected';
    governing_policy: {
        key: string;
        version: string;
        title: string;
        status: PolicyLifecycleStatus | 'missing';
        status_label: string;
        operative: boolean;
    };
    domains: Array<{
        key: string;
        label: string;
        actions: Array<{
            key: string;
            label: string;
            stage: 'decision' | 'approval' | 'execution' | 'verification';
        }>;
    }>;
    entries: AuthorityMatrixEntry[];
    deferred_decisions: Array<{
        key: string;
        label: string;
        state: 'unresolved' | 'counsel_review';
        reason: string;
    }>;
    evidence_records: Array<Record<string, unknown>>;
    counts: {
        domains: number;
        actions: number;
        entries: number;
        deferred_decisions: number;
        effective_entries: number;
        effective_holders: number;
        by_lifecycle: Record<AuthorityEntryLifecycleStatus, number>;
        by_resolution: Record<AuthorityResolutionStatus, number>;
    };
    reports: {
        conflicts: Array<{ code: string; message: string }>;
        source_gaps: Array<{ code: string; message: string }>;
        holder_gaps: Array<{ code: string; message: string }>;
        boundary_gaps: Array<{ code: string; message: string }>;
        evidence_gaps: Array<{ code: string; message: string }>;
    };
    principles: string[];
};

export type DecisionRecordLifecycleStatus =
    | 'proposed'
    | 'under_review'
    | 'decided'
    | 'effective'
    | 'superseded'
    | 'withdrawn';

export type DecisionRecordProjection = {
    key: string;
    title: string;
    lifecycle_status: DecisionRecordLifecycleStatus;
    lifecycle_status_label: string;
    context: {
        type: 'firm_governance' | 'firm_management';
        subject: string;
        reference_keys: string[];
    };
    materiality: 'routine' | 'material' | 'reserved';
    proposal: Record<string, unknown>;
    review: Record<string, unknown> | null;
    risk: Record<string, unknown>;
    authority: Record<string, unknown> | null;
    decision: Record<string, unknown> | null;
    authority_entry_label: string | null;
    approver_name: string | null;
    authority_resolved: boolean;
    temporal_state: string;
    may_execute: boolean;
    execution_occurred: boolean;
    verification_occurred: boolean;
};

export type DecisionRecordCompiler = {
    schema_version: number;
    compiler_status:
        'consistent' | 'consistent_with_gaps' | 'conflict_detected';
    governing_policies: Array<{
        purpose: string;
        key: string;
        version: string;
        title: string;
        required_for_effective_decision: boolean;
        status: PolicyLifecycleStatus | 'missing';
        status_label: string;
        operative: boolean;
    }>;
    record_requirements: Array<{
        key: string;
        label: string;
        question: string;
    }>;
    decision_records: DecisionRecordProjection[];
    execution_records: Array<Record<string, unknown>>;
    verification_records: Array<Record<string, unknown>>;
    evidence_records: Array<Record<string, unknown>>;
    counts: {
        decisions: number;
        executable_decisions: number;
        executions: number;
        verifications: number;
        evidence_records: number;
        by_lifecycle: Record<DecisionRecordLifecycleStatus, number>;
    };
    reports: {
        conflicts: Array<{ code: string; message: string }>;
        authority_gaps: Array<{ code: string; message: string }>;
        decision_gaps: Array<{ code: string; message: string }>;
        evidence_gaps: Array<{ code: string; message: string }>;
        readiness_gaps: Array<{ code: string; message: string }>;
    };
    principles: string[];
};
