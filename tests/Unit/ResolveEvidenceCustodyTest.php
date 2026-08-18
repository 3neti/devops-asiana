<?php

use App\EvidenceCustody\EvidenceCustodyDefinition;
use App\EvidenceCustody\ResolveEvidenceCustody;
use App\EvidenceIndex\ResolvedEvidenceIndex;

function emptyEvidenceIndex(): ResolvedEvidenceIndex
{
    return new ResolvedEvidenceIndex(
        schemaVersion: 1,
        requirements: [],
        records: [],
        indexedRecords: [],
        evidenceRecords: [],
        conflicts: [],
        pathGaps: [],
        evidenceGaps: [],
    );
}

test('an empty custody definition is consistent', function () {
    $resolved = app(ResolveEvidenceCustody::class)->handle(
        new EvidenceCustodyDefinition(1, [], []),
        emptyEvidenceIndex(),
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedRecords)->toHaveCount(0);
});

test('a custody record resolves only when source lifecycle facts are complete', function () {
    $index = new ResolvedEvidenceIndex(
        schemaVersion: 1,
        requirements: [],
        records: [],
        indexedRecords: [],
        evidenceRecords: [['key' => 'evidence-001', 'type' => 'change_record']],
        conflicts: [],
        pathGaps: [],
        evidenceGaps: [],
    );

    $record = [
        'key' => 'custody-001',
        'evidence_key' => 'evidence-001',
        'source_system' => 'matter-record',
        'source_reference' => 'matter-001',
        'custodian' => 'partner',
        'custody_events' => [[
            'actor' => 'partner',
            'action' => 'captured',
            'occurred_at' => '2026-08-18T10:00:00+08:00',
        ]],
        'retention' => [
            'policy_reference' => 'evidence.retention.default',
            'retain_until' => '2031-08-18T00:00:00+08:00',
            'review_at' => '2027-08-18T00:00:00+08:00',
        ],
        'integrity' => [
            'algorithm' => 'sha-256',
            'digest' => 'abc123',
            'verified_at' => '2026-08-18T10:05:00+08:00',
            'verified_by' => 'independent-reviewer',
        ],
        'disposition' => ['status' => 'retained'],
    ];

    $resolved = app(ResolveEvidenceCustody::class)->handle(
        new EvidenceCustodyDefinition(1, [], [$record]),
        $index,
    );

    expect($resolved->toArray())
        ->compiler_status->toBe('consistent')
        ->and($resolved->resolvedRecords)->toHaveCount(1)
        ->and($resolved->resolvedRecords[0]['custody_resolved'])->toBeTrue();
});

test('custody gaps remain explicit when evidence is incomplete or unindexed', function () {
    $resolved = app(ResolveEvidenceCustody::class)->handle(
        new EvidenceCustodyDefinition(1, [], [[
            'key' => 'custody-002',
            'evidence_key' => 'missing-evidence',
        ]]),
        emptyEvidenceIndex(),
    );

    $array = $resolved->toArray();

    expect($array['compiler_status'])->toBe('consistent_with_gaps')
        ->and($resolved->resolvedRecords)->toHaveCount(0)
        ->and($array['reports']['source_gaps'])->toContainEqual([
            'code' => 'custody_evidence_not_indexed',
            'message' => 'Custody record custody-002 references Evidence not present in the Index.',
        ])
        ->and(array_column($array['reports']['custody_gaps'], 'code'))->toContain('custody_history_missing')
        ->and(array_column($array['reports']['retention_gaps'], 'code'))->toContain('retention_basis_incomplete')
        ->and(array_column($array['reports']['integrity_gaps'], 'code'))->toContain('integrity_basis_incomplete')
        ->and(array_column($array['reports']['disposition_gaps'], 'code'))->toContain('disposition_incomplete');
});
