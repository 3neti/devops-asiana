<?php

namespace App\Incidents;

use App\Changes\ResolvedChanges;
use App\Engagements\ResolvedEngagements;
use App\Policies\PolicyLifecycleStatus;
use App\Policies\ResolvedPolicyRegistry;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveIncidents
{
    public function handle(
        IncidentDefinition $definition,
        ResolvedEngagements $engagements,
        ResolvedPolicyRegistry $policyRegistry,
        ?ResolvedChanges $changes = null,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedIncidents {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $decisionGaps */
        $decisionGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        /** @var list<array{code: string, message: string}> $readinessGaps */
        $readinessGaps = [];
        $effectiveAt = Carbon::instance($asOf ?? new DateTimeImmutable);
        $lifecycleCounts = array_fill_keys(array_map(
            static fn (IncidentLifecycleStatus $status): string => $status->value,
            IncidentLifecycleStatus::cases(),
        ), 0);

        $this->validateRequirements($definition->recordRequirements, $conflicts);
        $evidenceKeys = $this->validateEvidence($definition->evidenceRecords, $conflicts, $evidenceGaps);
        [$governingPolicies, $basePoliciesOperative] = $this->resolvePolicies(
            $definition,
            $policyRegistry,
            $effectiveAt,
            $conflicts,
            $readinessGaps,
        );
        /** @var array<string, array<string, mixed>> $openEngagements */
        $openEngagements = [];
        foreach ($engagements->engagements as $engagement) {
            if (($engagement['may_perform_client_work'] ?? false) === true && is_string($engagement['key'] ?? null)) {
                $openEngagements[$engagement['key']] = $engagement;
            }
        }
        $changeRecords = $changes === null ? [] : $changes->changeRecords;
        /** @var array<string, array<string, mixed>> $knownChanges */
        $knownChanges = [];
        foreach ($changeRecords as $change) {
            if (is_string($change['key'] ?? null)) {
                $knownChanges[$change['key']] = $change;
            }
        }
        /** @var list<string> $incidentKeys */
        $incidentKeys = [];
        /** @var list<array<string, mixed>> $resolved */
        $resolved = [];

        foreach ($definition->incidentRecords as $incident) {
            $resolved[] = $this->resolveIncident(
                incident: $incident,
                basePoliciesOperative: $basePoliciesOperative,
                governingPolicies: $governingPolicies,
                openEngagements: $openEngagements,
                knownChanges: $knownChanges,
                evidenceKeys: $evidenceKeys,
                incidentKeys: $incidentKeys,
                lifecycleCounts: $lifecycleCounts,
                conflicts: $conflicts,
                decisionGaps: $decisionGaps,
                evidenceGaps: $evidenceGaps,
            );
        }

        return new ResolvedIncidents(
            schemaVersion: $definition->schemaVersion,
            governingPolicies: $governingPolicies,
            recordRequirements: $definition->recordRequirements,
            incidentRecords: $resolved,
            evidenceRecords: $definition->evidenceRecords,
            lifecycleCounts: $lifecycleCounts,
            conflicts: $conflicts,
            decisionGaps: $decisionGaps,
            evidenceGaps: $evidenceGaps,
            readinessGaps: $readinessGaps,
        );
    }

    /**
     * @param  array<string, mixed>  $incident
     * @param  list<array<string, mixed>>  $governingPolicies
     * @param  array<string, array<string, mixed>>  $openEngagements
     * @param  array<string, array<string, mixed>>  $knownChanges
     * @param  list<string>  $evidenceKeys
     * @param  list<string>  $incidentKeys
     * @param  array<string, int>  $lifecycleCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array<string, mixed>
     */
    private function resolveIncident(
        array $incident,
        bool $basePoliciesOperative,
        array $governingPolicies,
        array $openEngagements,
        array $knownChanges,
        array $evidenceKeys,
        array &$incidentKeys,
        array &$lifecycleCounts,
        array &$conflicts,
        array &$decisionGaps,
        array &$evidenceGaps,
    ): array {
        $key = (string) ($incident['key'] ?? '');
        $status = IncidentLifecycleStatus::tryFrom((string) ($incident['lifecycle_status'] ?? ''));
        $type = IncidentType::tryFrom((string) ($incident['incident_type'] ?? ''));
        $severity = (string) ($incident['severity'] ?? '');
        $isMajor = ($incident['major_incident'] ?? false) === true;
        $clientImpacting = ($incident['impact']['client_impacting'] ?? false) === true;

        if ($key === '' || in_array($key, $incidentKeys, true)) {
            $conflicts[] = $this->issue('invalid_incident_key', 'An Incident Record has a missing or duplicate key.');
        }
        $incidentKeys[] = $key;

        if ($status === null || $type === null || $severity === '') {
            $conflicts[] = $this->issue('invalid_incident_classification', "Incident {$key} requires a valid lifecycle status, type, and severity.");
        }
        if ($status !== null) {
            $lifecycleCounts[$status->value]++;
        }

        $engagementKey = is_string($incident['engagement_key'] ?? null) ? $incident['engagement_key'] : '';
        $engagement = $openEngagements[$engagementKey] ?? null;
        if (! is_array($engagement)) {
            $conflicts[] = $this->issue('incident_without_open_engagement', "Incident {$key} does not reference an Open Engagement.");
        }

        $conditionalPoliciesOperative = $this->conditionalPoliciesOperative($governingPolicies, $type, $isMajor);
        $responsePoliciesOperative = $basePoliciesOperative && $conditionalPoliciesOperative;
        $declared = $status !== null && ! in_array($status, [IncidentLifecycleStatus::Detected, IncidentLifecycleStatus::Triaged, IncidentLifecycleStatus::FalsePositive], true);
        $declaration = $incident['declaration'] ?? null;

        if ($declared && ! $this->completeRecord($declaration, ['declared_by', 'authority_basis', 'declared_at', 'reason', 'evidence_record_key'])) {
            $decisionGaps[] = $this->issue('missing_incident_declaration', "Incident {$key} has progressed without an explicit declaration record.");
        }
        if ($declared && is_array($declaration) && (($declaration['incident_type'] ?? null) !== $type?->value || ($declaration['severity'] ?? null) !== $severity)) {
            $conflicts[] = $this->issue('incident_declaration_mismatch', "Incident {$key} declaration does not match its recorded type or severity.");
        }

        if ($declared) {
            $this->requireEvidence($declaration['evidence_record_key'] ?? null, $evidenceKeys, "Incident {$key} declaration", $evidenceGaps);
        }
        $this->validateDetection($incident, $key, $evidenceKeys, $decisionGaps, $evidenceGaps);
        $impactComplete = $this->validateImpact($incident, $key, $declared, $decisionGaps);
        $rolesComplete = $this->validateRoles($incident, $engagement, $key, $declared, $conflicts, $decisionGaps);
        $timelineComplete = $this->validateTimeline($incident, $key, $evidenceKeys, $conflicts, $decisionGaps, $evidenceGaps);
        $this->validateRelatedChanges($incident, $knownChanges, $key, $conflicts);

        $responseComplete = $this->validateResponseRecords($incident, $key, $status, $evidenceKeys, $decisionGaps, $evidenceGaps);
        $notificationsFinal = $this->validateNotifications($incident, $key, $declared, $clientImpacting, $evidenceKeys, $conflicts, $decisionGaps, $evidenceGaps);
        $restorationComplete = $this->validateRestoration($incident, $key, $status, $evidenceKeys, $decisionGaps, $evidenceGaps);
        $reviewRequired = $isMajor || $type === IncidentType::Security || $clientImpacting;
        $reviewComplete = $this->validateReview($incident, $key, $status, $reviewRequired, $evidenceKeys, $decisionGaps, $evidenceGaps);
        $actionsAccountable = $this->validateCorrectiveActions($incident, $key, $reviewRequired, $evidenceKeys, $decisionGaps, $evidenceGaps);

        $closurePrerequisites = $responsePoliciesOperative
            && $rolesComplete
            && $impactComplete
            && $timelineComplete
            && $responseComplete
            && $notificationsFinal
            && $restorationComplete
            && $reviewComplete
            && $actionsAccountable;
        $mayClose = $status === IncidentLifecycleStatus::UnderReview && $closurePrerequisites;
        $closure = $incident['closure'] ?? null;

        if ($status === IncidentLifecycleStatus::Closed) {
            if (! $closurePrerequisites || ! $this->completeRecord($closure, ['closed_by', 'authority_basis', 'closed_at', 'evidence_record_key'])) {
                $decisionGaps[] = $this->issue('premature_incident_closure', "Incident {$key} is Closed without every disclosure, restoration, review, corrective-action, and closure prerequisite.");
            }
            $this->requireEvidence($closure['evidence_record_key'] ?? null, $evidenceKeys, "Incident {$key} closure", $evidenceGaps);
        }

        $active = $status !== null && in_array($status, [
            IncidentLifecycleStatus::Declared,
            IncidentLifecycleStatus::Active,
            IncidentLifecycleStatus::Contained,
            IncidentLifecycleStatus::Recovering,
            IncidentLifecycleStatus::Monitoring,
        ], true);

        return array_merge($incident, [
            'lifecycle_status_label' => $status?->label() ?? 'Invalid',
            'incident_type_label' => $type?->label() ?? 'Invalid',
            'engagement_title' => $engagement['title'] ?? null,
            'client_name' => $engagement['client_name'] ?? null,
            'responsible_partner_name' => $engagement['responsible_partner']['name'] ?? null,
            'response_policies_operative' => $responsePoliciesOperative,
            'review_required' => $reviewRequired,
            'active_response' => $active,
            'service_restored' => $restorationComplete,
            'may_close_incident' => $mayClose,
            'operational_status' => match (true) {
                $status === IncidentLifecycleStatus::Closed && $closurePrerequisites => 'closed_verified',
                $status === IncidentLifecycleStatus::Closed => 'blocked_closure',
                $mayClose => 'ready_for_closure',
                $status === IncidentLifecycleStatus::ServiceRestored, $status === IncidentLifecycleStatus::UnderReview => 'restored_not_closed',
                $status === IncidentLifecycleStatus::FalsePositive => 'false_positive',
                $active => 'active_response',
                default => 'pending',
            },
        ]);
    }

    /**
     * @param  array<string, mixed>  $incident
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateDetection(array $incident, string $key, array $evidenceKeys, array &$decisionGaps, array &$evidenceGaps): void
    {
        $detection = $incident['detection'] ?? null;
        if (! $this->completeRecord($detection, ['observed_by', 'observed_at', 'source', 'summary', 'evidence_record_key'])) {
            $decisionGaps[] = $this->issue('missing_incident_detection', "Incident {$key} lacks a complete detection record.");
        }
        $this->requireEvidence($detection['evidence_record_key'] ?? null, $evidenceKeys, "Incident {$key} detection", $evidenceGaps);
    }

    /**
     * @param  array<string, mixed>  $incident
     * @param  list<array{code: string, message: string}>  $decisionGaps
     */
    private function validateImpact(array $incident, string $key, bool $required, array &$decisionGaps): bool
    {
        if (! $required) {
            return true;
        }

        $complete = $this->completeRecord($incident['impact'] ?? null, ['client_impacting', 'summary', 'affected_services']);
        if (! $complete) {
            $decisionGaps[] = $this->issue('missing_incident_impact', "Incident {$key} lacks a complete impact and affected-service statement.");
        }

        return $complete;
    }

    /**
     * @param  array<string, mixed>  $incident
     * @param  array<string, mixed>|null  $engagement
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     */
    private function validateRoles(array $incident, ?array $engagement, string $key, bool $required, array &$conflicts, array &$decisionGaps): bool
    {
        if (! $required) {
            return true;
        }
        $roles = $incident['roles'] ?? null;
        $complete = $this->completeRecord($roles, ['incident_commander', 'responsible_partner', 'technical_lead', 'communication_owner']);
        if (! $complete) {
            $decisionGaps[] = $this->issue('missing_incident_command_roles', "Incident {$key} lacks one or more named command roles.");

            return false;
        }
        $expected = $engagement['responsible_partner']['key'] ?? null;
        if ($expected !== null && ($roles['responsible_partner']['key'] ?? null) !== $expected) {
            $conflicts[] = $this->issue('responsible_partner_mismatch', "Incident {$key} names a Responsible Partner different from its Engagement.");

            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $incident
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateTimeline(array $incident, string $key, array $evidenceKeys, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): bool
    {
        $timeline = $incident['timeline'] ?? [];
        if ($timeline === []) {
            $decisionGaps[] = $this->issue('missing_incident_timeline', "Incident {$key} has no attributable timeline.");

            return false;
        }
        $entryKeys = [];
        $previous = null;
        $complete = true;
        foreach ($timeline as $entry) {
            $entryKey = (string) ($entry['key'] ?? '');
            $occurredAt = $this->date($entry['occurred_at'] ?? null);
            if ($entryKey === '' || in_array($entryKey, $entryKeys, true) || ! $this->completeRecord($entry, ['occurred_at', 'entry_type', 'actor', 'summary', 'source', 'evidence_record_key'])) {
                $conflicts[] = $this->issue('invalid_incident_timeline_entry', "Incident {$key} contains an incomplete or duplicate timeline entry.");
                $complete = false;
            }
            if ($occurredAt === null || ($previous !== null && $occurredAt->lessThan($previous))) {
                $conflicts[] = $this->issue('unordered_incident_timeline', "Incident {$key} timeline is not chronological.");
                $complete = false;
            }
            $previous = $occurredAt ?? $previous;
            $entryKeys[] = $entryKey;
            $this->requireEvidence($entry['evidence_record_key'] ?? null, $evidenceKeys, "Incident {$key} timeline entry {$entryKey}", $evidenceGaps);
        }

        return $complete;
    }

    /**
     * @param  array<string, mixed>  $incident
     * @param  array<string, array<string, mixed>>  $knownChanges
     * @param  list<array{code: string, message: string}>  $conflicts
     */
    private function validateRelatedChanges(array $incident, array $knownChanges, string $key, array &$conflicts): void
    {
        foreach ($incident['related_change_keys'] ?? [] as $changeKey) {
            if (! is_string($changeKey) || ! array_key_exists($changeKey, $knownChanges)) {
                $conflicts[] = $this->issue('unknown_related_change', "Incident {$key} references unknown Change {$changeKey}.");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $incident
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateResponseRecords(array $incident, string $key, ?IncidentLifecycleStatus $status, array $evidenceKeys, array &$decisionGaps, array &$evidenceGaps): bool
    {
        if ($status === null || in_array($status, [IncidentLifecycleStatus::Detected, IncidentLifecycleStatus::Triaged, IncidentLifecycleStatus::Declared, IncidentLifecycleStatus::FalsePositive], true)) {
            return true;
        }
        $complete = true;
        foreach (['preservation', 'containment', 'investigation', 'recovery'] as $section) {
            $record = $incident[$section] ?? null;
            if (! $this->completeRecord($record, ['owner', 'authority_basis', 'summary', 'result', 'evidence_record_key'])) {
                $decisionGaps[] = $this->issue("missing_incident_{$section}", "Incident {$key} lacks a complete {$section} record.");
                $complete = false;
            }
            $this->requireEvidence($record['evidence_record_key'] ?? null, $evidenceKeys, "Incident {$key} {$section}", $evidenceGaps);
        }

        return $complete;
    }

    /**
     * @param  array<string, mixed>  $incident
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateNotifications(array $incident, string $key, bool $declared, bool $clientImpacting, array $evidenceKeys, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): bool
    {
        if (! $declared) {
            return true;
        }
        $decisions = is_array($incident['notification_decisions'] ?? null) ? $incident['notification_decisions'] : [];
        /** @var array<string, array<string, mixed>> $byAudience */
        $byAudience = [];
        $final = true;
        foreach ($decisions as $decision) {
            if (is_array($decision) && is_string($decision['audience'] ?? null)) {
                if (array_key_exists($decision['audience'], $byAudience)) {
                    $conflicts[] = $this->issue('duplicate_notification_decision', "Incident {$key} has more than one current {$decision['audience']} notification decision.");
                    $final = false;
                }
                $byAudience[$decision['audience']] = $decision;
            }
        }
        $requiredAudiences = ['client', 'legal', 'regulatory', 'insurer'];
        foreach ($requiredAudiences as $audience) {
            $decision = $byAudience[$audience] ?? null;
            $outcome = is_array($decision) ? NotificationOutcome::tryFrom((string) ($decision['outcome'] ?? '')) : null;
            if (! $this->completeRecord($decision, ['audience', 'outcome', 'decided_by', 'authority_basis', 'reason', 'decided_at', 'evidence_record_key']) || $outcome === null) {
                $decisionGaps[] = $this->issue('missing_notification_decision', "Incident {$key} lacks a complete {$audience} notification decision.");
                $final = false;

                continue;
            }
            if (! $outcome->isFinal()) {
                $decisionGaps[] = $this->issue('pending_notification_decision', "Incident {$key} still has a pending {$audience} notification decision.");
                $final = false;
            }
            if ($audience === 'client' && $clientImpacting && $outcome !== NotificationOutcome::Notified) {
                $conflicts[] = $this->issue('client_impact_without_disclosure', "Client-impacting Incident {$key} is not recorded as disclosed to the Client.");
                $final = false;
            }
            $this->requireEvidence($decision['evidence_record_key'] ?? null, $evidenceKeys, "Incident {$key} {$audience} notification decision", $evidenceGaps);
        }

        return $final;
    }

    /**
     * @param  array<string, mixed>  $incident
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateRestoration(array $incident, string $key, ?IncidentLifecycleStatus $status, array $evidenceKeys, array &$decisionGaps, array &$evidenceGaps): bool
    {
        $requiresRestoration = $status !== null && in_array($status, [IncidentLifecycleStatus::ServiceRestored, IncidentLifecycleStatus::UnderReview, IncidentLifecycleStatus::Closed], true);
        if (! $requiresRestoration) {
            return false;
        }
        $restoration = $incident['restoration'] ?? null;
        $complete = $this->completeRecord($restoration, ['restored_by', 'restored_at', 'verification', 'verified_by', 'monitoring_result', 'evidence_record_key'])
            && ($restoration['verification'] ?? null) === 'verified';
        if (! $complete) {
            $decisionGaps[] = $this->issue('unverified_service_restoration', "Incident {$key} records restored service without complete independent verification and monitoring.");
        }
        $this->requireEvidence($restoration['evidence_record_key'] ?? null, $evidenceKeys, "Incident {$key} restoration", $evidenceGaps);

        return $complete;
    }

    /**
     * @param  array<string, mixed>  $incident
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateReview(array $incident, string $key, ?IncidentLifecycleStatus $status, bool $required, array $evidenceKeys, array &$decisionGaps, array &$evidenceGaps): bool
    {
        if (! $required) {
            return true;
        }
        $reviewDue = $status !== null && in_array($status, [IncidentLifecycleStatus::UnderReview, IncidentLifecycleStatus::Closed], true);
        if (! $reviewDue) {
            return false;
        }
        $review = $incident['post_incident_review'] ?? null;
        $complete = $this->completeRecord($review, ['facilitator', 'reviewed_at', 'impact', 'timeline_review', 'contributing_conditions', 'control_performance', 'decisions', 'recovery', 'communications', 'evidence_record_key'])
            && ($review['blameless'] ?? false) === true;
        if (! $complete) {
            $decisionGaps[] = $this->issue('missing_post_incident_review', "Incident {$key} requires a complete blameless post-incident review.");
        }
        $this->requireEvidence($review['evidence_record_key'] ?? null, $evidenceKeys, "Incident {$key} post-incident review", $evidenceGaps);

        return $complete;
    }

    /**
     * @param  array<string, mixed>  $incident
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function validateCorrectiveActions(array $incident, string $key, bool $required, array $evidenceKeys, array &$decisionGaps, array &$evidenceGaps): bool
    {
        if (! $required) {
            return true;
        }
        $actions = $incident['corrective_actions'] ?? [];
        if ($actions === []) {
            $decisionGaps[] = $this->issue('missing_corrective_actions', "Incident {$key} requires at least one owned corrective action or an evidenced no-action decision.");

            return false;
        }
        $complete = true;
        foreach ($actions as $action) {
            $valid = $this->completeRecord($action, ['key', 'description', 'owner', 'due_at', 'status']);
            if (! $valid) {
                $decisionGaps[] = $this->issue('unaccountable_corrective_action', "Incident {$key} has a corrective action without an owner, due date, or status.");
                $complete = false;
            }
            if (in_array($action['status'] ?? null, ['verified', 'closed'], true)) {
                $this->requireEvidence($action['evidence_record_key'] ?? null, $evidenceKeys, "Incident {$key} completed corrective action", $evidenceGaps);
            }
        }

        return $complete;
    }

    /** @param list<array<string, mixed>> $policies */
    private function conditionalPoliciesOperative(array $policies, ?IncidentType $type, bool $isMajor): bool
    {
        foreach ($policies as $policy) {
            $applies = $policy['applies_to'] === 'all'
                || ($policy['applies_to'] === 'security' && $type === IncidentType::Security)
                || ($policy['applies_to'] === 'major' && $isMajor);
            if ($applies && ! $policy['operative']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array{key: string, label: string, question: string}>  $requirements
     * @param  list<array{code: string, message: string}>  $conflicts
     */
    private function validateRequirements(array $requirements, array &$conflicts): void
    {
        $keys = [];
        foreach ($requirements as $requirement) {
            if (empty($requirement['key']) || in_array($requirement['key'], $keys, true) || empty($requirement['label']) || empty($requirement['question'])) {
                $conflicts[] = $this->issue('invalid_incident_requirement', 'An Incident Record requirement is missing, incomplete, or duplicated.');
            }
            $keys[] = $requirement['key'];
        }
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return list<string>
     */
    private function validateEvidence(array $records, array &$conflicts, array &$evidenceGaps): array
    {
        $keys = [];
        $valid = [];
        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? '');
            $unique = $key !== '' && ! in_array($key, $keys, true);
            if (! $unique) {
                $conflicts[] = $this->issue('invalid_incident_evidence_key', 'An Incident Evidence Record has a missing or duplicate key.');
            }
            $complete = $this->completeRecord($record, ['record_type', 'subject', 'actor', 'recorded_at', 'source', 'reason', 'state']);
            if (! $complete) {
                $evidenceGaps[] = $this->issue('incomplete_incident_evidence_record', "Evidence Record {$key} is incomplete.");
            }
            $keys[] = $key;
            if ($unique && $complete) {
                $valid[] = $key;
            }
        }

        return $valid;
    }

    /**
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $readinessGaps
     * @return array{list<array<string, mixed>>, bool}
     */
    private function resolvePolicies(IncidentDefinition $definition, ResolvedPolicyRegistry $registry, Carbon $asOf, array &$conflicts, array &$readinessGaps): array
    {
        $resolved = [];
        $baseOperative = true;
        $evidenceKeys = array_column($registry->evidenceRecords, 'key');
        foreach ($definition->governingPolicies as $reference) {
            $policy = null;
            foreach ($registry->policies as $candidate) {
                if (($candidate['key'] ?? null) === $reference['key']) {
                    $policy = $candidate;
                    break;
                }
            }
            $version = null;
            if (is_array($policy)) {
                foreach ($policy['versions'] ?? [] as $candidateVersion) {
                    if (is_array($candidateVersion) && ($candidateVersion['version'] ?? null) === $reference['version']) {
                        $version = $candidateVersion;
                        break;
                    }
                }
            }
            if (! is_array($policy) || ! is_array($version)) {
                $conflicts[] = $this->issue('missing_incident_governing_policy', "Incident response references missing policy {$reference['key']} version {$reference['version']}.");
                $operative = false;
                $status = null;
            } else {
                $status = PolicyLifecycleStatus::tryFrom((string) ($version['status'] ?? ''));
                $approval = $version['approval'] ?? null;
                $effectiveAt = $this->date($version['effective_at'] ?? null);
                $operative = $status === PolicyLifecycleStatus::Effective
                    && ($version['content_integrity'] ?? null) === 'verified'
                    && $effectiveAt !== null
                    && $effectiveAt->lessThanOrEqualTo($asOf)
                    && is_array($approval)
                    && ($approval['outcome'] ?? null) === 'approved'
                    && in_array($approval['evidence_record_key'] ?? null, $evidenceKeys, true);
            }
            if ($reference['required_for_declaration'] && ! $operative) {
                $baseOperative = false;
                $readinessGaps[] = $this->issue('incident_policy_not_operative', "{$reference['key']} version {$reference['version']} is not operative for Incident declaration.");
            }
            $statusValue = $status === null ? 'missing' : $status->value;
            $statusLabel = $status === null ? 'Missing' : $status->label();
            $resolved[] = array_merge($reference, [
                'title' => $policy['title'] ?? $reference['key'],
                'status' => $statusValue,
                'status_label' => $statusLabel,
                'operative' => $operative,
            ]);
        }

        return [$resolved, $baseOperative];
    }

    /**
     * @param  list<string>  $evidenceKeys
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    private function requireEvidence(mixed $key, array $evidenceKeys, string $subject, array &$evidenceGaps): void
    {
        if (! is_string($key) || ! in_array($key, $evidenceKeys, true)) {
            $evidenceGaps[] = $this->issue('missing_incident_evidence', "{$subject} does not reference a complete Evidence Record.");
        }
    }

    /** @param list<string> $fields */
    private function completeRecord(mixed $record, array $fields): bool
    {
        if (! is_array($record)) {
            return false;
        }
        foreach ($fields as $field) {
            if (! array_key_exists($field, $record) || $record[$field] === '' || $record[$field] === null || $record[$field] === []) {
                return false;
            }
        }

        return true;
    }

    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return ['code' => $code, 'message' => $message];
    }
}
