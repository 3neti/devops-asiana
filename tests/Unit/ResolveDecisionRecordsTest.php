<?php

use App\AuthorityMatrix\ResolvedAuthorityMatrix;
use App\DecisionRecords\DecisionRecordDefinition;
use App\DecisionRecords\ResolveDecisionRecords;
use App\Policies\ResolvedPolicyRegistry;
use DateTimeImmutable;

function decisionRecordDefinition(): DecisionRecordDefinition
{
    $definition = json_decode(file_get_contents(__DIR__.'/../../resources/institution/decision-records.json'), true, flags: JSON_THROW_ON_ERROR);

    return DecisionRecordDefinition::fromArray($definition);
}

/** @return array<string, mixed> */
function decisionEvidence(string $key, string $type, string $actor): array
{
    return [
        'key' => $key,
        'record_type' => $type,
        'actor' => $actor,
        'occurred_at' => '2026-08-18T09:00:00+08:00',
        'source' => 'Institutional decision record test fixture',
        'reason' => 'Prove a separate institutional stage.',
        'approval' => 'Test fixture only',
        'state' => 'accepted',
        'supporting_evidence' => ['fixture'],
    ];
}

function effectiveDecisionPolicies(): ResolvedPolicyRegistry
{
    $policies = array_map(static fn (array $policy): array => [
        'key' => $policy['key'],
        'current_status' => 'effective',
        'current_status_label' => 'Effective',
        'current' => [
            'version' => $policy['version'],
            'status' => 'effective',
            'effective_at' => '2026-01-01T00:00:00+08:00',
        ],
    ], decisionRecordDefinition()->governingPolicies);

    return new ResolvedPolicyRegistry(1, $policies, [], [], [], [], [], []);
}

function effectiveDecisionAuthority(bool $requiresClientMandate = false): ResolvedAuthorityMatrix
{
    return new ResolvedAuthorityMatrix(
        1,
        ['operative' => true],
        [],
        [[
            'key' => 'managing-partner-ordinary-management',
            'action_label' => 'Exercise ordinary Firm management',
            'grants_firm_authority' => true,
            'effective_holder_keys' => ['angelica-santos'],
            'effective_holder_names' => ['Angelica Anaïs C. Santos'],
            'scope' => ['client_mandate_required' => $requiresClientMandate],
            'separation' => [
                'self_approval_permitted' => false,
                'execution_separate' => true,
                'independent_verification_required' => true,
            ],
        ]],
        [],
        [],
        ['active' => 1],
        ['effective' => 1],
        [],
        [],
        [],
        [],
        [],
    );
}

/** @return array<string, mixed> */
function completeDecision(): array
{
    return [
        'key' => 'decision-office-lease',
        'title' => 'Approve bounded Firm office lease preparation',
        'lifecycle_status' => 'effective',
        'context' => [
            'type' => 'firm_management',
            'subject' => 'Firm office lease preparation',
            'reference_keys' => [],
        ],
        'materiality' => 'material',
        'proposal' => [
            'proposed_by_identity_key' => 'lester-hurtado',
            'proposed_at' => '2026-08-18T08:00:00+08:00',
            'statement' => 'Prepare a lease within a separately approved boundary.',
            'reason' => 'Support Firm operations.',
            'alternatives' => ['Continue remote operations'],
            'evidence_record_key' => 'evidence-proposal',
        ],
        'review' => [
            'reviewer_identity_keys' => ['angelica-santos'],
            'conflicts_checked' => true,
            'related_party_disclosed' => true,
            'completed_at' => '2026-08-18T08:30:00+08:00',
            'evidence_record_key' => 'evidence-review',
        ],
        'risk' => [
            'classification' => 'moderate',
            'owner_identity_key' => 'angelica-santos',
            'acceptance_required' => false,
            'acceptance' => null,
        ],
        'authority' => [
            'authority_matrix_entry_key' => 'managing-partner-ordinary-management',
            'approver_identity_key' => 'angelica-santos',
        ],
        'decision' => [
            'outcome' => 'approved',
            'decided_at' => '2026-08-18T09:00:00+08:00',
            'effective_at' => '2026-08-18T09:00:00+08:00',
            'expires_at' => null,
            'conditions' => [],
            'permits_execution' => true,
            'evidence_record_key' => 'evidence-decision',
        ],
        'supersedes_decision_key' => null,
        'disposition' => null,
    ];
}

/** @param array<string, mixed> $changes */
function decisionDefinitionWith(array $changes): DecisionRecordDefinition
{
    $definition = decisionRecordDefinition();

    return new DecisionRecordDefinition(
        $definition->schemaVersion,
        $definition->governingPolicies,
        $definition->recordRequirements,
        $changes['decisions'] ?? $definition->decisions,
        $changes['executions'] ?? $definition->executions,
        $changes['verifications'] ?? $definition->verifications,
        $changes['evidence'] ?? $definition->evidenceRecords,
    );
}

/** @return array<string, mixed> */
function resolveDecisionFixture(DecisionRecordDefinition $definition, ?ResolvedAuthorityMatrix $authority = null): array
{
    return (new ResolveDecisionRecords)->handle(
        $definition,
        effectiveDecisionPolicies(),
        $authority ?? effectiveDecisionAuthority(),
        new DateTimeImmutable('2026-08-18T12:00:00+08:00'),
    )->toArray();
}

test('canonical state preserves an empty ledger and exposes readiness gaps', function () {
    $definition = decisionRecordDefinition();
    $emptyPolicies = new ResolvedPolicyRegistry(1, [], [], [], [], [], [], []);
    $emptyAuthority = new ResolvedAuthorityMatrix(1, [], [], [], [], [], [], ['effective' => 0], [], [], [], [], []);
    $resolved = (new ResolveDecisionRecords)->handle($definition, $emptyPolicies, $emptyAuthority)->toArray();

    expect($resolved['counts']['decisions'])->toBe(0)
        ->and($resolved['counts']['executions'])->toBe(0)
        ->and($resolved['counts']['verifications'])->toBe(0)
        ->and(array_column($resolved['reports']['readiness_gaps'], 'code'))->toContain('no_effective_authority_entry');
});

test('a complete decision under an effective holder becomes executable without implying execution', function () {
    $resolved = resolveDecisionFixture(decisionDefinitionWith([
        'decisions' => [completeDecision()],
        'evidence' => [
            decisionEvidence('evidence-proposal', 'Proposal Record', 'Lester B. Hurtado'),
            decisionEvidence('evidence-review', 'Review Record', 'Angelica Anaïs C. Santos'),
            decisionEvidence('evidence-decision', 'Decision Record', 'Angelica Anaïs C. Santos'),
        ],
    ]));
    $decision = $resolved['decision_records'][0];

    expect($decision['authority_resolved'])->toBeTrue()
        ->and($decision['may_execute'])->toBeTrue()
        ->and($decision['execution_occurred'])->toBeFalse()
        ->and($decision['verification_occurred'])->toBeFalse()
        ->and($resolved['compiler_status'])->toBe('consistent');
});

test('draft governing policy blocks an otherwise complete effective decision', function () {
    $definition = decisionDefinitionWith([
        'decisions' => [completeDecision()],
        'evidence' => [
            decisionEvidence('evidence-proposal', 'Proposal Record', 'Lester B. Hurtado'),
            decisionEvidence('evidence-review', 'Review Record', 'Angelica Anaïs C. Santos'),
            decisionEvidence('evidence-decision', 'Decision Record', 'Angelica Anaïs C. Santos'),
        ],
    ]);
    $draftPolicies = effectiveDecisionPolicies();
    $policies = $draftPolicies->policies;
    $policies[0]['current_status'] = 'draft';
    $policies[0]['current_status_label'] = 'Draft';
    $draftPolicies = new ResolvedPolicyRegistry(1, $policies, [], [], [], [], [], []);
    $resolved = (new ResolveDecisionRecords)->handle(
        $definition,
        $draftPolicies,
        effectiveDecisionAuthority(),
        new DateTimeImmutable('2026-08-18T12:00:00+08:00'),
    )->toArray();

    expect($resolved['decision_records'][0]['may_execute'])->toBeFalse()
        ->and(array_column($resolved['reports']['readiness_gaps'], 'code'))->toContain('governing_policy_not_effective');
});

test('execution cannot backfill an inactive or missing approval', function () {
    $decision = completeDecision();
    $decision['authority'] = null;
    $resolved = resolveDecisionFixture(decisionDefinitionWith([
        'decisions' => [$decision],
        'executions' => [[
            'key' => 'execution-office-lease',
            'decision_key' => 'decision-office-lease',
            'executed_by_identity_key' => 'angelica-santos',
            'executed_at' => '2026-08-18T10:00:00+08:00',
            'action' => 'Prepared lease.',
            'result' => 'completed',
            'evidence_record_key' => 'evidence-execution',
        ]],
        'evidence' => [
            decisionEvidence('evidence-proposal', 'Proposal Record', 'Lester B. Hurtado'),
            decisionEvidence('evidence-review', 'Review Record', 'Angelica Anaïs C. Santos'),
            decisionEvidence('evidence-decision', 'Decision Record', 'Angelica Anaïs C. Santos'),
            decisionEvidence('evidence-execution', 'Execution Record', 'Angelica Anaïs C. Santos'),
        ],
    ]));

    expect($resolved['decision_records'][0]['may_execute'])->toBeFalse()
        ->and($resolved['execution_records'][0]['authorized_by_decision'])->toBeFalse()
        ->and(array_column($resolved['reports']['decision_gaps'], 'code'))->toContain('execution_without_effective_decision');
});

test('a Client action cannot enter the Firm-only decision compiler', function () {
    $resolved = resolveDecisionFixture(decisionDefinitionWith([
        'decisions' => [completeDecision()],
        'evidence' => [
            decisionEvidence('evidence-proposal', 'Proposal Record', 'Lester B. Hurtado'),
            decisionEvidence('evidence-review', 'Review Record', 'Angelica Anaïs C. Santos'),
            decisionEvidence('evidence-decision', 'Decision Record', 'Angelica Anaïs C. Santos'),
        ],
    ]), effectiveDecisionAuthority(true));

    expect(array_column($resolved['reports']['conflicts'], 'code'))
        ->toContain('client_action_outside_decision_boundary')
        ->and($resolved['decision_records'][0]['may_execute'])->toBeFalse();
});

test('an executor cannot independently verify their own action', function () {
    $resolved = resolveDecisionFixture(decisionDefinitionWith([
        'decisions' => [completeDecision()],
        'executions' => [[
            'key' => 'execution-office-lease',
            'decision_key' => 'decision-office-lease',
            'executed_by_identity_key' => 'angelica-santos',
            'executed_at' => '2026-08-18T10:00:00+08:00',
            'action' => 'Prepared lease.',
            'result' => 'completed',
            'evidence_record_key' => 'evidence-execution',
        ]],
        'verifications' => [[
            'key' => 'verification-office-lease',
            'decision_key' => 'decision-office-lease',
            'execution_record_key' => 'execution-office-lease',
            'verified_by_identity_key' => 'angelica-santos',
            'verified_at' => '2026-08-18T11:00:00+08:00',
            'criteria' => ['Decision conditions satisfied'],
            'result' => 'passed',
            'evidence_record_key' => 'evidence-verification',
        ]],
        'evidence' => [
            decisionEvidence('evidence-proposal', 'Proposal Record', 'Lester B. Hurtado'),
            decisionEvidence('evidence-review', 'Review Record', 'Angelica Anaïs C. Santos'),
            decisionEvidence('evidence-decision', 'Decision Record', 'Angelica Anaïs C. Santos'),
            decisionEvidence('evidence-execution', 'Execution Record', 'Angelica Anaïs C. Santos'),
            decisionEvidence('evidence-verification', 'Verification Record', 'Angelica Anaïs C. Santos'),
        ],
    ]));

    expect(array_column($resolved['reports']['conflicts'], 'code'))->toContain('execution_self_verification')
        ->and($resolved['verification_records'][0]['independent'])->toBeFalse();
});
