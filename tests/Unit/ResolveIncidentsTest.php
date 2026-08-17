<?php

use App\Engagements\ResolvedEngagements;
use App\Incidents\IncidentDefinition;
use App\Incidents\ResolveIncidents;
use App\Policies\PolicyRegistryDefinition;
use App\Policies\ResolvedPolicyRegistry;
use App\Policies\ResolvePolicyRegistry;

function incidentDefinitionArray(): array
{
    return json_decode(file_get_contents(__DIR__.'/../../resources/institution/incidents.json'), true, flags: JSON_THROW_ON_ERROR);
}

function incidentEvidence(string $key): array
{
    return [
        'key' => $key,
        'record_type' => 'Incident Evidence',
        'subject' => 'Hypothetical Incident',
        'actor' => 'Hypothetical authorized actor',
        'recorded_at' => '2026-08-18T12:00:00+08:00',
        'source' => 'Hypothetical institutional record',
        'reason' => 'Supports an Incident fact or decision',
        'state' => 'final',
    ];
}

function incidentPolicies(bool $effective = true): ResolvedPolicyRegistry
{
    $registry = json_decode(file_get_contents(__DIR__.'/../../resources/institution/policies.json'), true, flags: JSON_THROW_ON_ERROR);
    if ($effective) {
        foreach ($registry['policies'] as &$policy) {
            if (! in_array($policy['key'], ['incident-management', 'authority-and-delegation', 'information-security', 'business-continuity-dr'], true)) {
                continue;
            }
            $policy['versions'][0]['status'] = 'effective';
            $policy['versions'][0]['effective_at'] = '2026-08-01T00:00:00+08:00';
            $policy['versions'][0]['approval'] = [
                'key' => 'APR-'.strtoupper($policy['key']),
                'outcome' => 'approved',
                'approver' => 'Hypothetical policy authority',
                'authority_basis' => 'Hypothetical delegated authority',
                'decided_at' => '2026-07-30T09:00:00+08:00',
                'evidence_record_key' => 'EVD-INCIDENT-POLICY',
            ];
        }
        unset($policy);
        $registry['evidence_records'][] = incidentEvidence('EVD-INCIDENT-POLICY');
    }

    return (new ResolvePolicyRegistry)->handle(PolicyRegistryDefinition::fromArray($registry));
}

function incidentEngagements(): ResolvedEngagements
{
    return new ResolvedEngagements(
        schemaVersion: 1,
        governingPolicies: [],
        openingRequirements: [],
        engagements: [[
            'key' => 'ENG-HYPOTHETICAL-0001',
            'title' => 'Hypothetical Managed Cloud Operations',
            'client_name' => 'Hypothetical Rural Bank, Inc.',
            'may_perform_client_work' => true,
            'responsible_partner' => ['key' => 'angelica-santos', 'name' => 'Angelica Anaïs C. Santos'],
        ]],
        evidenceRecords: [],
        lifecycleCounts: [],
        conflicts: [],
        decisionGaps: [],
        evidenceGaps: [],
        readinessGaps: [],
    );
}

function completeIncident(string $status = 'under_review'): array
{
    return [
        'key' => 'INC-HYPOTHETICAL-0001',
        'title' => 'Hypothetical payment service interruption',
        'lifecycle_status' => $status,
        'incident_type' => 'operational',
        'severity' => 'SEV-2',
        'major_incident' => true,
        'engagement_key' => 'ENG-HYPOTHETICAL-0001',
        'detection' => [
            'observed_by' => 'Hypothetical On-call Engineer',
            'observed_at' => '2026-08-18T09:00:00+08:00',
            'source' => 'Service health alert',
            'summary' => 'Sustained payment request failures were observed.',
            'evidence_record_key' => 'EVD-INC-DETECTION',
        ],
        'declaration' => [
            'declared_by' => 'Hypothetical Incident Authority',
            'authority_basis' => 'Incident Management Policy',
            'declared_at' => '2026-08-18T09:05:00+08:00',
            'incident_type' => 'operational',
            'severity' => 'SEV-2',
            'reason' => 'Material Client service degradation requires coordinated response.',
            'evidence_record_key' => 'EVD-INC-DECLARATION',
        ],
        'roles' => [
            'incident_commander' => ['key' => 'hypothetical-commander', 'name' => 'Hypothetical Incident Commander'],
            'responsible_partner' => ['key' => 'angelica-santos', 'name' => 'Angelica Anaïs C. Santos'],
            'technical_lead' => ['key' => 'hypothetical-technical-lead', 'name' => 'Hypothetical Technical Lead'],
            'communication_owner' => ['key' => 'hypothetical-communicator', 'name' => 'Hypothetical Communication Owner'],
        ],
        'impact' => [
            'client_impacting' => true,
            'summary' => 'Payment requests failed for a bounded period.',
            'affected_services' => ['Hypothetical Payment Service'],
        ],
        'timeline' => [[
            'key' => 'TL-001',
            'occurred_at' => '2026-08-18T09:00:00+08:00',
            'entry_type' => 'fact',
            'actor' => 'Hypothetical On-call Engineer',
            'summary' => 'Alert confirmed against service telemetry.',
            'source' => 'Monitoring system',
            'evidence_record_key' => 'EVD-INC-TIMELINE',
        ]],
        'related_change_keys' => [],
        'preservation' => incidentResponseRecord('EVD-INC-PRESERVATION', 'Logs and service state preserved.'),
        'containment' => incidentResponseRecord('EVD-INC-CONTAINMENT', 'Affected traffic isolated.'),
        'investigation' => incidentResponseRecord('EVD-INC-INVESTIGATION', 'Verified facts separated from working hypotheses.'),
        'recovery' => incidentResponseRecord('EVD-INC-RECOVERY', 'Known-good service state restored.'),
        'restoration' => [
            'restored_by' => 'Hypothetical Technical Lead',
            'restored_at' => '2026-08-18T10:00:00+08:00',
            'verification' => 'verified',
            'verified_by' => 'Hypothetical Independent Verifier',
            'monitoring_result' => 'Stable throughout the defined observation period.',
            'evidence_record_key' => 'EVD-INC-RESTORATION',
        ],
        'notification_decisions' => [
            incidentNotification('client', 'notified'),
            incidentNotification('legal', 'not_required'),
            incidentNotification('regulatory', 'not_required'),
            incidentNotification('insurer', 'not_required'),
        ],
        'post_incident_review' => [
            'facilitator' => 'Hypothetical Review Facilitator',
            'reviewed_at' => '2026-08-18T11:00:00+08:00',
            'blameless' => true,
            'impact' => 'Bounded Client service interruption.',
            'timeline_review' => 'Response timeline reviewed.',
            'contributing_conditions' => ['Insufficient upstream isolation'],
            'control_performance' => 'Detection operated; isolation control needs improvement.',
            'decisions' => 'Command decisions and authority were reviewed.',
            'recovery' => 'Recovery restored known-good state.',
            'communications' => 'Client updates were timely and factual.',
            'evidence_record_key' => 'EVD-INC-REVIEW',
        ],
        'corrective_actions' => [[
            'key' => 'CA-HYPOTHETICAL-001',
            'description' => 'Improve upstream failure isolation.',
            'owner' => 'Hypothetical Technical Lead',
            'due_at' => '2026-09-01T17:00:00+08:00',
            'status' => 'open',
        ]],
    ];
}

function incidentResponseRecord(string $evidence, string $summary): array
{
    return [
        'owner' => 'Hypothetical Technical Lead',
        'authority_basis' => 'Incident command delegation',
        'summary' => $summary,
        'result' => 'completed',
        'evidence_record_key' => $evidence,
    ];
}

function incidentNotification(string $audience, string $outcome): array
{
    return [
        'audience' => $audience,
        'outcome' => $outcome,
        'decided_by' => 'Hypothetical Communication Authority',
        'authority_basis' => 'Incident Management Policy and Engagement',
        'reason' => $outcome === 'notified' ? 'Client-impacting Incident requires disclosure.' : 'No applicable notification trigger identified.',
        'decided_at' => '2026-08-18T10:15:00+08:00',
        'evidence_record_key' => 'EVD-INC-NOTIFICATION',
    ];
}

function resolveIncidentRecord(array $incident, bool $effectivePolicies = true): array
{
    $definition = incidentDefinitionArray();
    $definition['incident_records'] = [$incident];
    foreach (['DETECTION', 'DECLARATION', 'TIMELINE', 'PRESERVATION', 'CONTAINMENT', 'INVESTIGATION', 'RECOVERY', 'RESTORATION', 'NOTIFICATION', 'REVIEW', 'CLOSURE'] as $suffix) {
        $definition['evidence_records'][] = incidentEvidence("EVD-INC-{$suffix}");
    }

    return (new ResolveIncidents)->handle(
        IncidentDefinition::fromArray($definition),
        incidentEngagements(),
        incidentPolicies($effectivePolicies),
        asOf: new DateTimeImmutable('2026-08-18T12:00:00+08:00'),
    )->toArray();
}

test('canonical Incident registry remains honest about declaration readiness', function () {
    $resolved = (new ResolveIncidents)->handle(
        IncidentDefinition::fromArray(incidentDefinitionArray()),
        incidentEngagements(),
        incidentPolicies(false),
        asOf: new DateTimeImmutable('2026-08-18T12:00:00+08:00'),
    )->toArray();

    expect($resolved['compiler_status'])->toBe('consistent_with_gaps')
        ->and($resolved['counts']['incident_records'])->toBe(0)
        ->and($resolved['reports']['readiness_gaps'])->toHaveCount(2);
});

test('an observed event does not imply an Incident declaration', function () {
    $incident = completeIncident('detected');
    unset($incident['declaration'], $incident['roles'], $incident['preservation'], $incident['containment'], $incident['investigation'], $incident['recovery'], $incident['restoration'], $incident['notification_decisions'], $incident['post_incident_review'], $incident['corrective_actions']);

    $resolved = resolveIncidentRecord($incident);

    expect($resolved['incident_records'][0]['operational_status'])->toBe('pending')
        ->and($resolved['incident_records'][0]['active_response'])->toBeFalse();
});

test('service restoration never implies Incident closure', function () {
    $resolved = resolveIncidentRecord(completeIncident('service_restored'));

    expect($resolved['incident_records'][0]['service_restored'])->toBeTrue()
        ->and($resolved['incident_records'][0]['may_close_incident'])->toBeFalse()
        ->and($resolved['incident_records'][0]['operational_status'])->toBe('restored_not_closed');
});

test('client impact cannot be closed without recorded Client disclosure', function () {
    $incident = completeIncident();
    $incident['notification_decisions'][0] = incidentNotification('client', 'not_required');

    $resolved = resolveIncidentRecord($incident);

    expect($resolved['compiler_status'])->toBe('conflict_detected')
        ->and($resolved['incident_records'][0]['may_close_incident'])->toBeFalse()
        ->and($resolved['reports']['conflicts'])->toContainEqual([
            'code' => 'client_impact_without_disclosure',
            'message' => 'Client-impacting Incident INC-HYPOTHETICAL-0001 is not recorded as disclosed to the Client.',
        ]);
});

test('a reviewed Incident becomes closure-ready before closure is separately authorized', function () {
    $resolved = resolveIncidentRecord(completeIncident());

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['incident_records'][0]['may_close_incident'])->toBeTrue()
        ->and($resolved['incident_records'][0]['operational_status'])->toBe('ready_for_closure');
});

test('closure requires an explicit evidenced authority record', function () {
    $incident = completeIncident('closed');
    $incident['closure'] = [
        'closed_by' => 'Hypothetical Responsible Partner',
        'authority_basis' => 'Incident Management Policy',
        'closed_at' => '2026-08-18T11:30:00+08:00',
        'evidence_record_key' => 'EVD-INC-CLOSURE',
    ];

    $resolved = resolveIncidentRecord($incident);

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['incident_records'][0]['may_close_incident'])->toBeFalse()
        ->and($resolved['incident_records'][0]['operational_status'])->toBe('closed_verified');
});
