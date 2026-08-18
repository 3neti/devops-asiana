<?php

use App\EvidenceIndex\EvidenceIndexDefinition;
use App\EvidenceIndex\ResolveEvidenceIndex;

function evidenceIndexEvidence(): array
{
    return [['key' => 'evidence-001', 'record_type' => 'Index Evidence', 'subject' => 'matter-001', 'actor' => 'partner', 'recorded_at' => '2026-08-18T10:00:00+08:00', 'source' => 'Matter record', 'reason' => 'Preserve traceability.', 'state' => 'accepted']];
}

test('an empty Evidence Index is consistent', function () {
    $resolved = (new ResolveEvidenceIndex)->handle(new EvidenceIndexDefinition(1, [], [], []))->toArray();

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['counts']['indexed_records'])->toBe(0);
});

test('a complete Matter record preserves the Client Engagement Matter and Evidence path', function () {
    $resolved = (new ResolveEvidenceIndex)->handle(new EvidenceIndexDefinition(1, [], [[
        'key' => 'index-matter-001', 'artifact_type' => 'matter', 'artifact_key' => 'matter-001', 'client_key' => 'client-001', 'engagement_key' => 'engagement-001', 'matter_key' => 'matter-001', 'evidence_record_keys' => ['evidence-001'],
    ]], evidenceIndexEvidence()))->toArray();

    expect($resolved['compiler_status'])->toBe('consistent')
        ->and($resolved['indexed_records'][0]['indexed'])->toBeTrue()
        ->and($resolved['counts']['by_artifact_type']['matter'])->toBe(1);
});

test('missing traceability or Evidence remains visible as a gap', function () {
    $resolved = (new ResolveEvidenceIndex)->handle(new EvidenceIndexDefinition(1, [], [[
        'key' => 'index-event-001', 'artifact_type' => 'matter_event', 'artifact_key' => 'event-001', 'evidence_record_keys' => ['missing-evidence'],
    ]], []))->toArray();

    expect($resolved['compiler_status'])->toBe('consistent_with_gaps')
        ->and($resolved['indexed_records'])->toBeEmpty()
        ->and(array_column($resolved['reports']['path_gaps'], 'code'))->toContain('evidence_index_engagement_missing')
        ->and(array_column($resolved['reports']['evidence_gaps'], 'code'))->toContain('evidence_index_unknown_evidence');
});
