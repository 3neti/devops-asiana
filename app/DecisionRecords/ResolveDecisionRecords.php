<?php

namespace App\DecisionRecords;

use App\AuthorityMatrix\ResolvedAuthorityMatrix;
use App\GovernanceMeetings\ResolvedGovernanceMeetings;
use App\Policies\ResolvedPolicyRegistry;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveDecisionRecords
{
    public function handle(
        DecisionRecordDefinition $definition,
        ResolvedPolicyRegistry $policies,
        ResolvedAuthorityMatrix $authorityMatrix,
        ?ResolvedGovernanceMeetings $governanceMeetings = null,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedDecisionRecords {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $authorityGaps */
        $authorityGaps = [];
        /** @var list<array{code: string, message: string}> $decisionGaps */
        $decisionGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        /** @var list<array{code: string, message: string}> $admissionGaps */
        $admissionGaps = [];
        /** @var list<array{code: string, message: string}> $readinessGaps */
        $readinessGaps = [];
        $effectiveAt = Carbon::instance($asOf ?? new DateTimeImmutable);
        $policyIndex = $this->indexByKey($policies->policies);
        $authorityIndex = $this->indexByKey($authorityMatrix->entries);
        $evidenceIndex = $this->indexByKey($definition->evidenceRecords);
        $collectiveCandidates = $this->collectiveCandidates($governanceMeetings);
        $collectiveAdmissions = $this->resolveCollectiveAdmissions(
            $definition->collectiveAdmissions,
            $collectiveCandidates,
            $this->indexByKey($definition->decisions),
            $evidenceIndex,
            $effectiveAt,
            $conflicts,
            $admissionGaps,
            $evidenceGaps,
        );
        $collectiveAdmissionIndex = $this->indexByKey($collectiveAdmissions);
        $admittedSourceKeys = array_column(array_filter($collectiveAdmissions, static fn (array $admission): bool => $admission['grants_collective_approval_basis'] === true), 'source_key');
        $availableCollectiveCandidates = array_values(array_filter($collectiveCandidates, static fn (array $candidate): bool => ! in_array($candidate['source_key'], $admittedSourceKeys, true)));
        $governingPolicies = $this->resolveGoverningPolicies($definition->governingPolicies, $policyIndex, $readinessGaps, $conflicts);
        $governingPoliciesOperative = array_all($governingPolicies, static fn (array $policy): bool => $policy['operative'] === true);

        if (($authorityMatrix->resolutionCounts['effective'] ?? 0) === 0) {
            $readinessGaps[] = $this->issue('no_effective_authority_entry', 'No Authority Matrix entry is currently effective, so no Decision Record can become operative.');
        }

        $lifecycleCounts = array_fill_keys(
            array_map(static fn (DecisionRecordLifecycleStatus $status): string => $status->value, DecisionRecordLifecycleStatus::cases()),
            0,
        );
        $decisions = [];
        $decisionKeys = [];

        foreach ($definition->decisions as $decision) {
            $decisions[] = $this->resolveDecision(
                $decision,
                $authorityIndex,
                $collectiveAdmissionIndex,
                $evidenceIndex,
                $governingPoliciesOperative,
                $effectiveAt,
                $decisionKeys,
                $lifecycleCounts,
                $conflicts,
                $authorityGaps,
                $decisionGaps,
                $evidenceGaps,
            );
        }

        $decisionIndex = $this->indexByKey($decisions);
        $executions = $this->resolveExecutions($definition->executions, $decisionIndex, $evidenceIndex, $conflicts, $decisionGaps, $evidenceGaps);
        $executionIndex = $this->indexByKey($executions);
        $verifications = $this->resolveVerifications($definition->verifications, $decisionIndex, $executionIndex, $evidenceIndex, $conflicts, $decisionGaps, $evidenceGaps);
        $executedDecisionKeys = array_column($executions, 'decision_key');
        $verifiedDecisionKeys = array_column($verifications, 'decision_key');
        $decisions = array_map(static fn (array $decision): array => [
            ...$decision,
            'execution_occurred' => in_array($decision['key'], $executedDecisionKeys, true),
            'verification_occurred' => in_array($decision['key'], $verifiedDecisionKeys, true),
        ], $decisions);

        return new ResolvedDecisionRecords(
            $definition->schemaVersion,
            $governingPolicies,
            $definition->recordRequirements,
            $collectiveAdmissions,
            $availableCollectiveCandidates,
            $decisions,
            $executions,
            $verifications,
            $definition->evidenceRecords,
            $lifecycleCounts,
            $conflicts,
            $authorityGaps,
            $decisionGaps,
            $evidenceGaps,
            $admissionGaps,
            $readinessGaps,
        );
    }

    /**
     * @param  array<string, mixed>  $decision
     * @param  array<string, array<string, mixed>>  $authorityIndex
     * @param  array<string, array<string, mixed>>  $collectiveAdmissions
     * @param  array<string, array<string, mixed>>  $evidenceIndex
     * @param  list<string>  $decisionKeys
     * @param  array<string, int>  $lifecycleCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $authorityGaps
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array<string, mixed>
     */
    private function resolveDecision(array $decision, array $authorityIndex, array $collectiveAdmissions, array $evidenceIndex, bool $governingPoliciesOperative, Carbon $asOf, array &$decisionKeys, array &$lifecycleCounts, array &$conflicts, array &$authorityGaps, array &$decisionGaps, array &$evidenceGaps): array
    {
        $conflictCount = count($conflicts);
        $gapCount = count($authorityGaps) + count($decisionGaps) + count($evidenceGaps);
        $key = (string) ($decision['key'] ?? '');
        $lifecycle = DecisionRecordLifecycleStatus::tryFrom((string) ($decision['lifecycle_status'] ?? ''));
        $contextType = $decision['context']['type'] ?? null;

        if ($key === '' || in_array($key, $decisionKeys, true)) {
            $conflicts[] = $this->issue('invalid_decision_key', 'A Decision Record has a missing or duplicate key.');
        }
        $decisionKeys[] = $key;
        if ($lifecycle === null) {
            $conflicts[] = $this->issue('invalid_decision_lifecycle', "Decision {$key} has an invalid lifecycle state.");
        } else {
            $lifecycleCounts[$lifecycle->value]++;
        }
        if (! in_array($contextType, ['firm_governance', 'firm_management'], true)) {
            $conflicts[] = $this->issue('unsupported_decision_context', "Decision {$key} is outside the Firm governance and management boundary of this compiler.");
        }

        $proposal = $decision['proposal'] ?? null;
        $review = $decision['review'] ?? null;
        $risk = $decision['risk'] ?? [];
        $authority = $decision['authority'] ?? null;
        $outcome = $decision['decision'] ?? null;
        $authorityBasisType = is_array($authority) ? ($authority['basis_type'] ?? 'single_holder') : null;
        $collectiveAdmission = $authorityBasisType === 'collective_governance'
            ? ($collectiveAdmissions[$authority['collective_admission_key'] ?? ''] ?? null)
            : null;
        $authorityEntryKey = $authorityBasisType === 'collective_governance'
            ? ($collectiveAdmission['source_snapshot']['authority_matrix_entry_key'] ?? null)
            : (is_array($authority) ? ($authority['authority_matrix_entry_key'] ?? null) : null);
        $authorityEntry = is_string($authorityEntryKey) ? ($authorityIndex[$authorityEntryKey] ?? null) : null;
        $approverKey = is_array($authority) ? ($authority['approver_identity_key'] ?? null) : null;
        $proposerKey = is_array($proposal) ? ($proposal['proposed_by_identity_key'] ?? null) : null;
        $materiality = $decision['materiality'] ?? null;

        if (! is_array($proposal)) {
            $decisionGaps[] = $this->issue('missing_proposal', "Decision {$key} has no explicit proposal.");
        } elseif (! $this->hasEvidence($proposal['evidence_record_key'] ?? null, $evidenceIndex)) {
            $evidenceGaps[] = $this->issue('missing_proposal_evidence', "Decision {$key} has no complete proposal Evidence Record.");
        }
        if (! in_array($materiality, ['routine', 'material', 'reserved'], true)) {
            $conflicts[] = $this->issue('invalid_decision_materiality', "Decision {$key} has an invalid materiality.");
        }

        if (! in_array($authorityBasisType, ['single_holder', 'collective_governance'], true)) {
            $conflicts[] = $this->issue('invalid_authority_basis_type', "Decision {$key} has an unsupported authority basis type.");
        } elseif ($authorityBasisType === 'collective_governance') {
            if ($collectiveAdmission === null || ($collectiveAdmission['grants_collective_approval_basis'] ?? false) !== true || ($collectiveAdmission['decision_record_key'] ?? null) !== $key) {
                $authorityGaps[] = $this->issue('missing_collective_admission', "Decision {$key} lacks an admitted collective Governance Meeting outcome addressed to this exact record.");
            } elseif (($decision['title'] ?? null) !== ($collectiveAdmission['source_snapshot']['title'] ?? null)
                || $contextType !== 'firm_governance'
                || $materiality !== ($collectiveAdmission['source_snapshot']['materiality'] ?? null)
                || ($outcome['decided_at'] ?? null) !== ($collectiveAdmission['source_snapshot']['decided_at'] ?? null)) {
                $conflicts[] = $this->issue('collective_decision_projection_mismatch', "Decision {$key} contradicts its admitted collective source.");
            }
            $participantKeys = $collectiveAdmission['source_snapshot']['participant_identity_keys'] ?? [];
            if ($authorityEntry === null || ($authorityEntry['grants_firm_authority'] ?? false) !== true || array_diff($participantKeys, $authorityEntry['effective_holder_keys'] ?? []) !== []) {
                $authorityGaps[] = $this->issue('inactive_collective_decision_authority', "Decision {$key} lacks effective collective authority for every admitted participant.");
            }
        } elseif ($authorityEntry === null) {
            $authorityGaps[] = $this->issue('missing_decision_authority', "Decision {$key} does not cite a known Authority Matrix entry.");
        } else {
            if (($authorityEntry['grants_firm_authority'] ?? false) !== true) {
                $authorityGaps[] = $this->issue('inactive_decision_authority', "Decision {$key} cites an Authority Matrix entry that is not effective.");
            }
            if (($authorityEntry['scope']['client_mandate_required'] ?? false) === true) {
                $conflicts[] = $this->issue('client_action_outside_decision_boundary', "Decision {$key} cites an authority entry requiring Client Mandate, which this compiler does not resolve.");
            }
            if (! in_array($approverKey, $authorityEntry['effective_holder_keys'] ?? [], true)) {
                $authorityGaps[] = $this->issue('approver_not_authority_holder', "Decision {$key} was not approved by an effective holder of the cited Matrix entry.");
            }
            if (($authorityEntry['separation']['self_approval_permitted'] ?? false) !== true && $proposerKey !== null && $proposerKey === $approverKey) {
                $conflicts[] = $this->issue('decision_self_approval', "Decision {$key} was proposed and approved by the same identity contrary to the Matrix entry.");
            }
        }

        if (in_array($lifecycle, [DecisionRecordLifecycleStatus::Decided, DecisionRecordLifecycleStatus::Effective, DecisionRecordLifecycleStatus::Superseded], true)) {
            if (! is_array($review) || ($review['conflicts_checked'] ?? false) !== true || ($review['related_party_disclosed'] ?? false) !== true) {
                $decisionGaps[] = $this->issue('incomplete_decision_review', "Decision {$key} lacks a completed conflicts and related-party review.");
            } elseif (! $this->hasEvidence($review['evidence_record_key'] ?? null, $evidenceIndex)) {
                $evidenceGaps[] = $this->issue('missing_review_evidence', "Decision {$key} has no complete review Evidence Record.");
            }
            if (! is_array($outcome) || ! in_array($outcome['outcome'] ?? null, ['approved', 'approved_with_conditions', 'rejected', 'deferred'], true)) {
                $decisionGaps[] = $this->issue('missing_decision_outcome', "Decision {$key} has no valid explicit outcome.");
            } elseif (! $this->hasEvidence($outcome['evidence_record_key'] ?? null, $evidenceIndex)) {
                $evidenceGaps[] = $this->issue('missing_decision_evidence', "Decision {$key} has no complete decision Evidence Record.");
            }
        }

        $riskLevel = $risk['classification'] ?? null;
        if (! in_array($riskLevel, ['low', 'moderate', 'high', 'critical'], true) || empty($risk['owner_identity_key'])) {
            $decisionGaps[] = $this->issue('incomplete_risk_record', "Decision {$key} lacks a valid risk classification or owner.");
        }
        if (($risk['acceptance_required'] ?? false) === true) {
            $acceptance = $risk['acceptance'] ?? null;
            if (! is_array($acceptance) || ! $this->hasEvidence($acceptance['evidence_record_key'] ?? null, $evidenceIndex)) {
                $decisionGaps[] = $this->issue('missing_risk_acceptance', "Decision {$key} requires separate, evidenced risk acceptance.");
            } else {
                $riskAuthority = $authorityIndex[$acceptance['authority_matrix_entry_key'] ?? ''] ?? null;
                $riskAcceptor = $acceptance['accepted_by_identity_key'] ?? null;
                if ($riskAuthority === null || ($riskAuthority['grants_firm_authority'] ?? false) !== true || ! in_array($riskAcceptor, $riskAuthority['effective_holder_keys'] ?? [], true)) {
                    $authorityGaps[] = $this->issue('invalid_risk_acceptance_authority', "Decision {$key} lacks an effective authority holder for risk acceptance.");
                }
            }
        }
        if (in_array($riskLevel, ['high', 'critical'], true) && ($risk['acceptance_required'] ?? false) !== true) {
            $decisionGaps[] = $this->issue('high_risk_not_accepted', "Decision {$key} classifies high or critical risk without requiring explicit acceptance.");
        }
        if (in_array($materiality, ['material', 'reserved'], true) && is_array($review) && in_array($proposerKey, $review['reviewer_identity_keys'] ?? [], true)) {
            $conflicts[] = $this->issue('proposal_self_review', "Decision {$key} has a proposer acting as reviewer for a material decision.");
        }

        $approved = is_array($outcome) && in_array($outcome['outcome'] ?? null, ['approved', 'approved_with_conditions'], true);
        if (is_array($outcome) && in_array($outcome['outcome'] ?? null, ['rejected', 'deferred'], true) && ($outcome['permits_execution'] ?? false) === true) {
            $conflicts[] = $this->issue('non_approved_execution_permission', "Decision {$key} permits execution despite a rejected or deferred outcome.");
        }
        $effectiveFrom = $this->date(is_array($outcome) ? ($outcome['effective_at'] ?? null) : null);
        $expiresAt = $this->date(is_array($outcome) ? ($outcome['expires_at'] ?? null) : null);
        $proposedAt = $this->date(is_array($proposal) ? ($proposal['proposed_at'] ?? null) : null);
        $reviewedAt = $this->date(is_array($review) ? ($review['completed_at'] ?? null) : null);
        $decidedAt = $this->date(is_array($outcome) ? ($outcome['decided_at'] ?? null) : null);
        if (($proposedAt !== null && $reviewedAt !== null && $reviewedAt->isBefore($proposedAt)) || ($reviewedAt !== null && $decidedAt !== null && $decidedAt->isBefore($reviewedAt))) {
            $conflicts[] = $this->issue('invalid_decision_chronology', "Decision {$key} has review or decision events out of chronological order.");
        }
        if ($effectiveFrom !== null && $expiresAt !== null && $expiresAt->lessThanOrEqualTo($effectiveFrom)) {
            $conflicts[] = $this->issue('invalid_decision_period', "Decision {$key} expires before or at its effective time.");
        }
        if ($lifecycle === DecisionRecordLifecycleStatus::Effective && (! $approved || $effectiveFrom === null)) {
            $conflicts[] = $this->issue('invalid_effective_decision', "Decision {$key} cannot be Effective without an approved outcome and explicit effective time.");
        }

        $withinPeriod = $effectiveFrom !== null && ! $effectiveFrom->isAfter($asOf) && ($expiresAt === null || $expiresAt->isAfter($asOf));
        $complete = count($conflicts) === $conflictCount && count($authorityGaps) + count($decisionGaps) + count($evidenceGaps) === $gapCount;
        $institutionallyValid = $complete
            && $governingPoliciesOperative
            && $lifecycle === DecisionRecordLifecycleStatus::Effective
            && $approved
            && $withinPeriod;
        $mayExecute = $institutionallyValid
            && ($outcome['permits_execution'] ?? false) === true;

        return [
            ...$decision,
            'lifecycle_status_label' => $lifecycle?->label() ?? 'Invalid',
            'authority_entry_label' => $authorityEntry['action_label'] ?? null,
            'authority_basis_type' => $authorityBasisType,
            'collective_participant_identity_keys' => $collectiveAdmission['source_snapshot']['participant_identity_keys'] ?? [],
            'collective_vote_tally' => $collectiveAdmission['source_snapshot']['vote_tally'] ?? null,
            'collective_source_evidence_record_keys' => $collectiveAdmission['source_snapshot']['source_evidence_record_keys'] ?? [],
            'approver_name' => $authorityBasisType === 'single_holder' && $authorityEntry !== null ? $this->holderName($authorityEntry, $approverKey) : null,
            'authority_resolved' => $authorityBasisType === 'collective_governance'
                ? $collectiveAdmission !== null && ($collectiveAdmission['grants_collective_approval_basis'] ?? false) === true && $authorityEntry !== null && ($authorityEntry['grants_firm_authority'] ?? false) === true && array_diff($collectiveAdmission['source_snapshot']['participant_identity_keys'] ?? [], $authorityEntry['effective_holder_keys'] ?? []) === []
                : $authorityEntry !== null && ($authorityEntry['grants_firm_authority'] ?? false) === true && in_array($approverKey, $authorityEntry['effective_holder_keys'] ?? [], true),
            'temporal_state' => $this->temporalState($effectiveFrom, $expiresAt, $asOf),
            'institutionally_valid' => $institutionallyValid,
            'may_execute' => $mayExecute,
            'execution_occurred' => false,
            'verification_occurred' => false,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  array<string, array<string, mixed>>  $decisions
     * @param  array<string, array<string, mixed>>  $evidence
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return list<array<string, mixed>>
     */
    private function resolveExecutions(array $records, array $decisions, array $evidence, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): array
    {
        $resolved = [];
        $keys = [];
        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? '');
            $decision = $decisions[$record['decision_key'] ?? ''] ?? null;
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_execution_key', 'An Execution Record has a missing or duplicate key.');
            }
            $keys[] = $key;
            if ($decision === null || ($decision['may_execute'] ?? false) !== true) {
                $decisionGaps[] = $this->issue('execution_without_effective_decision', "Execution {$key} is not supported by an executable Decision Record.");
            }
            $executedAt = $this->date($record['executed_at'] ?? null);
            $decisionEffectiveAt = $this->date($decision['decision']['effective_at'] ?? null);
            if ($executedAt === null || ($decisionEffectiveAt !== null && $executedAt->isBefore($decisionEffectiveAt))) {
                $conflicts[] = $this->issue('invalid_execution_time', "Execution {$key} lacks a valid time at or after the Decision became effective.");
            }
            if (! $this->hasEvidence($record['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_execution_evidence', "Execution {$key} has no complete Evidence Record.");
            }
            $resolved[] = [...$record, 'authorized_by_decision' => $decision !== null && ($decision['may_execute'] ?? false) === true];
        }

        return $resolved;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  array<string, array<string, mixed>>  $decisions
     * @param  array<string, array<string, mixed>>  $executions
     * @param  array<string, array<string, mixed>>  $evidence
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $decisionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return list<array<string, mixed>>
     */
    private function resolveVerifications(array $records, array $decisions, array $executions, array $evidence, array &$conflicts, array &$decisionGaps, array &$evidenceGaps): array
    {
        $resolved = [];
        $keys = [];
        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? '');
            $execution = $executions[$record['execution_record_key'] ?? ''] ?? null;
            $decision = $decisions[$record['decision_key'] ?? ''] ?? null;
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_verification_key', 'A Verification Record has a missing or duplicate key.');
            }
            $keys[] = $key;
            if ($execution === null || $decision === null || ($execution['decision_key'] ?? null) !== ($record['decision_key'] ?? null)) {
                $decisionGaps[] = $this->issue('verification_without_execution', "Verification {$key} does not cite a matching Decision and Execution Record.");
            }
            if ($execution !== null && ($execution['executed_by_identity_key'] ?? null) === ($record['verified_by_identity_key'] ?? null)) {
                $conflicts[] = $this->issue('execution_self_verification', "Verification {$key} was performed by the executor.");
            }
            $executedAt = $this->date($execution['executed_at'] ?? null);
            $verifiedAt = $this->date($record['verified_at'] ?? null);
            if ($verifiedAt === null || ($executedAt !== null && $verifiedAt->isBefore($executedAt))) {
                $conflicts[] = $this->issue('invalid_verification_time', "Verification {$key} lacks a valid time at or after execution.");
            }
            if (! in_array($record['result'] ?? null, ['passed', 'failed', 'partial'], true)) {
                $conflicts[] = $this->issue('invalid_verification_result', "Verification {$key} has an invalid result.");
            }
            if (! $this->hasEvidence($record['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_verification_evidence', "Verification {$key} has no complete Evidence Record.");
            }
            $resolved[] = [...$record, 'independent' => $execution !== null && ($execution['executed_by_identity_key'] ?? null) !== ($record['verified_by_identity_key'] ?? null)];
        }

        return $resolved;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectiveCandidates(?ResolvedGovernanceMeetings $governanceMeetings): array
    {
        if ($governanceMeetings === null) {
            return [];
        }

        $candidates = [];
        foreach ($governanceMeetings->meetings as $meeting) {
            foreach ($meeting['agenda_items'] ?? [] as $agendaItem) {
                $candidate = $agendaItem['decision_record_candidate'] ?? null;
                if (is_array($candidate)) {
                    $candidates[] = [
                        ...$candidate,
                        'source_key' => $candidate['meeting_key'].'::'.$candidate['agenda_item_key'],
                    ];
                }
            }
        }

        return $candidates;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  list<array<string, mixed>>  $candidates
     * @param  array<string, array<string, mixed>>  $decisions
     * @param  array<string, array<string, mixed>>  $evidence
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $admissionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return list<array<string, mixed>>
     */
    private function resolveCollectiveAdmissions(array $records, array $candidates, array $decisions, array $evidence, Carbon $asOf, array &$conflicts, array &$admissionGaps, array &$evidenceGaps): array
    {
        $resolved = [];
        $keys = [];
        $candidateIndex = [];
        $sourceOccurrences = array_count_values(array_map(static fn (array $record): string => (string) ($record['meeting_key'] ?? '').'::'.(string) ($record['agenda_item_key'] ?? ''), $records));
        $decisionOccurrences = array_count_values(array_map(static fn (array $record): string => (string) ($record['decision_record_key'] ?? ''), $records));
        foreach ($candidates as $candidate) {
            $candidateIndex[$candidate['source_key']] = $candidate;
        }

        foreach ($records as $record) {
            $issueCount = count($conflicts) + count($admissionGaps) + count($evidenceGaps);
            $key = (string) ($record['key'] ?? '');
            $sourceKey = (string) ($record['meeting_key'] ?? '').'::'.(string) ($record['agenda_item_key'] ?? '');
            $decisionKey = (string) ($record['decision_record_key'] ?? '');
            $lifecycle = CollectiveAdmissionLifecycleStatus::tryFrom((string) ($record['lifecycle_status'] ?? ''));
            $candidate = $candidateIndex[$sourceKey] ?? null;

            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_collective_admission_key', 'A collective admission has a missing or duplicate key.');
            }
            if (($sourceOccurrences[$sourceKey] ?? 0) > 1) {
                $conflicts[] = $this->issue('duplicate_collective_source_admission', "Governance source {$sourceKey} is admitted more than once.");
            }
            if ($decisionKey === '' || ($decisionOccurrences[$decisionKey] ?? 0) > 1) {
                $conflicts[] = $this->issue('duplicate_collective_target_admission', 'A Decision Record is missing or receives more than one collective admission.');
            }
            $keys[] = $key;
            if ($lifecycle === null) {
                $conflicts[] = $this->issue('invalid_collective_admission_lifecycle', "Collective admission {$key} has an invalid lifecycle state.");
            }
            if (! isset($decisions[$decisionKey])) {
                $admissionGaps[] = $this->issue('collective_admission_target_missing', "Collective admission {$key} does not address an existing Decision Record.");
            }
            if ($candidate === null) {
                $admissionGaps[] = $this->issue('collective_candidate_missing', "Collective admission {$key} does not cite an eligible Governance Meeting candidate.");
            } elseif (($record['source_snapshot'] ?? null) !== $this->collectiveSourceSnapshot($candidate)) {
                $conflicts[] = $this->issue('collective_source_snapshot_mismatch', "Collective admission {$key} contradicts its Governance Meeting source.");
            }
            if (! $this->hasEvidence($record['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_collective_admission_evidence', "Collective admission {$key} lacks a complete Evidence Record.");
            }
            $recordedAt = $this->date($record['recorded_at'] ?? null);
            $decidedAt = $this->date($candidate['decided_at'] ?? null);
            if ($recordedAt === null || $recordedAt->isAfter($asOf) || ($decidedAt !== null && $recordedAt->isBefore($decidedAt))) {
                $conflicts[] = $this->issue('invalid_collective_admission_time', "Collective admission {$key} has an invalid recording time.");
            }

            $valid = $lifecycle === CollectiveAdmissionLifecycleStatus::Admitted
                && count($conflicts) + count($admissionGaps) + count($evidenceGaps) === $issueCount;
            $resolved[] = [
                ...$record,
                'lifecycle_status_label' => $lifecycle?->label() ?? 'Invalid',
                'source_key' => $sourceKey,
                'grants_collective_approval_basis' => $valid,
            ];
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return array<string, mixed>
     */
    private function collectiveSourceSnapshot(array $candidate): array
    {
        return [
            'title' => $candidate['title'],
            'materiality' => $candidate['materiality'],
            'participant_identity_keys' => $candidate['collective_authority']['participant_identity_keys'],
            'vote_tally' => $candidate['vote_tally'],
            'authority_matrix_entry_key' => $candidate['collective_authority']['authority_matrix_entry_key'],
            'decided_at' => $candidate['decided_at'],
            'outcome_evidence_record_key' => $candidate['evidence_record_key'],
            'source_evidence_record_keys' => $candidate['source_evidence_record_keys'] ?? [$candidate['evidence_record_key']],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $requirements
     * @param  array<string, array<string, mixed>>  $policies
     * @param  list<array{code: string, message: string}>  $readinessGaps
     * @param  list<array{code: string, message: string}>  $conflicts
     * @return list<array<string, mixed>>
     */
    private function resolveGoverningPolicies(array $requirements, array $policies, array &$readinessGaps, array &$conflicts): array
    {
        $resolved = [];
        foreach ($requirements as $requirement) {
            $policy = $policies[$requirement['key'] ?? ''] ?? null;
            $versionMatches = $policy !== null && ($policy['current']['version'] ?? null) === ($requirement['version'] ?? null);
            if (! $versionMatches) {
                $conflicts[] = $this->issue('governing_policy_mismatch', "Required policy {$requirement['key']} {$requirement['version']} is missing or not current.");
            }
            $operative = $versionMatches && (($policy['current']['operative'] ?? null) === true
                || (! array_key_exists('operative', $policy['current'] ?? []) && ($policy['current_status'] ?? null) === 'effective'));
            if (($requirement['required_for_effective_decision'] ?? false) === true && ! $operative) {
                $readinessGaps[] = $this->issue('governing_policy_not_effective', "{$requirement['title']} {$requirement['version']} is not Effective.");
            }
            $resolved[] = [
                ...$requirement,
                'status' => $policy['current_status'] ?? 'missing',
                'status_label' => $policy['current_status_label'] ?? 'Missing',
                'operative' => $operative,
            ];
        }

        return $resolved;
    }

    /** @param array<string, array<string, mixed>> $evidence */
    private function hasEvidence(mixed $key, array $evidence): bool
    {
        if (! is_string($key) || ! isset($evidence[$key])) {
            return false;
        }

        foreach (['record_type', 'actor', 'occurred_at', 'source', 'reason', 'approval', 'state', 'supporting_evidence'] as $field) {
            if (! array_key_exists($field, $evidence[$key]) || $evidence[$key][$field] === '' || $evidence[$key][$field] === []) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array<string, array<string, mixed>>
     */
    private function indexByKey(array $records): array
    {
        $index = [];
        foreach ($records as $record) {
            if (is_string($record['key'] ?? null)) {
                $index[$record['key']] = $record;
            }
        }

        return $index;
    }

    /** @param array<string, mixed> $entry */
    private function holderName(array $entry, mixed $holderKey): ?string
    {
        $index = array_search($holderKey, $entry['effective_holder_keys'] ?? [], true);

        return $index === false ? null : ($entry['effective_holder_names'][$index] ?? null);
    }

    private function temporalState(?Carbon $effectiveAt, ?Carbon $expiresAt, Carbon $asOf): string
    {
        return match (true) {
            $effectiveAt === null => 'not_effective',
            $effectiveAt->isAfter($asOf) => 'future',
            $expiresAt !== null && ! $expiresAt->isAfter($asOf) => 'expired',
            default => 'current',
        };
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
        return compact('code', 'message');
    }
}
