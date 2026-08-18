<?php

namespace App\GovernanceMeetings;

use App\AuthorityMatrix\ResolvedAuthorityMatrix;
use App\Partnership\ResolvedPartnership;
use App\Policies\ResolvedPolicyRegistry;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveGovernanceMeetings
{
    public function handle(
        GovernanceMeetingDefinition $definition,
        ResolvedPartnership $partnership,
        ResolvedPolicyRegistry $policies,
        ResolvedAuthorityMatrix $authorityMatrix,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedGovernanceMeetings {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $meetingGaps */
        $meetingGaps = [];
        /** @var list<array{code: string, message: string}> $authorityGaps */
        $authorityGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        /** @var list<array{code: string, message: string}> $readinessGaps */
        $readinessGaps = [];
        $effectiveAt = Carbon::instance($asOf ?? new DateTimeImmutable);
        $policyIndex = $this->indexByKey($policies->policies);
        $authorityIndex = $this->indexByKey($authorityMatrix->entries);
        $evidenceIndex = $this->indexByKey($definition->evidenceRecords);
        $governingPartners = $this->resolveGoverningPartners($partnership);
        $partnerIndex = $this->indexByKey($governingPartners);
        $governingPolicies = $this->resolveGoverningPolicies($definition->governingPolicies, $policyIndex, $readinessGaps, $conflicts);
        $policiesOperative = array_all($governingPolicies, static fn (array $policy): bool => $policy['operative'] === true);
        $decisionRules = $this->resolveDecisionRules($definition->decisionRules, $readinessGaps, $conflicts);

        if (($authorityIndex['founding-partner-reserved-matter-participation']['grants_firm_authority'] ?? false) !== true) {
            $readinessGaps[] = $this->issue('collective_governance_authority_not_effective', 'Founding Partner Reserved Matter participation is not yet an effective Authority Matrix entry.');
        }

        $this->validateReservedMatterCatalog($definition->reservedMatterCatalog, $partnership->constitution['reserved_matters'] ?? [], $conflicts);

        $lifecycleCounts = array_fill_keys(
            array_map(static fn (GovernanceMeetingLifecycleStatus $status): string => $status->value, GovernanceMeetingLifecycleStatus::cases()),
            0,
        );
        $outcomeCounts = array_fill_keys(['adopted', 'rejected', 'deadlock_unresolved', 'tied_pending_mechanism', 'no_decision'], 0);
        $meetings = [];
        $meetingKeys = [];

        foreach ($definition->meetings as $meeting) {
            $meetings[] = $this->resolveMeeting(
                $meeting,
                $partnerIndex,
                $authorityIndex,
                $evidenceIndex,
                $decisionRules,
                $definition->reservedMatterCatalog,
                $policiesOperative,
                $effectiveAt,
                $meetingKeys,
                $lifecycleCounts,
                $outcomeCounts,
                $conflicts,
                $meetingGaps,
                $authorityGaps,
                $evidenceGaps,
            );
        }

        return new ResolvedGovernanceMeetings(
            $definition->schemaVersion,
            $governingPolicies,
            $definition->meetingRequirements,
            $decisionRules,
            $definition->reservedMatterCatalog,
            $governingPartners,
            $meetings,
            $definition->evidenceRecords,
            $lifecycleCounts,
            $outcomeCounts,
            $conflicts,
            $meetingGaps,
            $authorityGaps,
            $evidenceGaps,
            $readinessGaps,
        );
    }

    /**
     * @param  array<string, mixed>  $meeting
     * @param  array<string, array<string, mixed>>  $partners
     * @param  array<string, array<string, mixed>>  $authority
     * @param  array<string, array<string, mixed>>  $evidence
     * @param  array<string, mixed>  $decisionRules
     * @param  list<array<string, string>>  $reservedMatters
     * @param  list<string>  $meetingKeys
     * @param  array<string, int>  $lifecycleCounts
     * @param  array<string, int>  $outcomeCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $meetingGaps
     * @param  list<array{code: string, message: string}>  $authorityGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array<string, mixed>
     */
    private function resolveMeeting(array $meeting, array $partners, array $authority, array $evidence, array $decisionRules, array $reservedMatters, bool $policiesOperative, Carbon $asOf, array &$meetingKeys, array &$lifecycleCounts, array &$outcomeCounts, array &$conflicts, array &$meetingGaps, array &$authorityGaps, array &$evidenceGaps): array
    {
        $meetingIssueCount = count($conflicts) + count($meetingGaps) + count($authorityGaps) + count($evidenceGaps);
        $key = (string) ($meeting['key'] ?? '');
        $lifecycle = GovernanceMeetingLifecycleStatus::tryFrom((string) ($meeting['lifecycle_status'] ?? ''));
        if ($key === '' || in_array($key, $meetingKeys, true)) {
            $conflicts[] = $this->issue('invalid_meeting_key', 'A Governance Meeting has a missing or duplicate key.');
        }
        $meetingKeys[] = $key;
        if ($lifecycle === null) {
            $conflicts[] = $this->issue('invalid_meeting_lifecycle', "Meeting {$key} has an invalid lifecycle state.");
        } else {
            $lifecycleCounts[$lifecycle->value]++;
        }

        $scheduledAt = $this->date($meeting['scheduled_start'] ?? null);
        $convenedAt = $this->date($meeting['convened_at'] ?? null);
        $concludedAt = $this->date($meeting['concluded_at'] ?? null);
        if (($convenedAt !== null && $scheduledAt !== null && $convenedAt->isBefore($scheduledAt)) || ($concludedAt !== null && $convenedAt !== null && $concludedAt->isBefore($convenedAt))) {
            $conflicts[] = $this->issue('invalid_meeting_chronology', "Meeting {$key} has events out of chronological order.");
        }

        $notice = $meeting['notice'] ?? null;
        if (! is_array($notice) || ! $this->hasEvidence($notice['evidence_record_key'] ?? null, $evidence)) {
            $evidenceGaps[] = $this->issue('missing_meeting_notice_evidence', "Meeting {$key} lacks evidenced notice and agenda circulation.");
        }
        $attendance = $this->resolveAttendance($key, $meeting['attendance'] ?? [], $partners, $evidence, $conflicts, $meetingGaps, $evidenceGaps);
        $presentPartnerKeys = array_column(array_filter($attendance, static fn (array $record): bool => $record['status'] === 'present'), 'identity_key');
        $presentWeight = array_sum(array_map(static fn (string $partnerKey): int => (int) ($partners[$partnerKey]['governance_weight'] ?? 0), $presentPartnerKeys));
        if ($lifecycle === GovernanceMeetingLifecycleStatus::Concluded && ! $this->hasEvidence($meeting['minutes_evidence_record_key'] ?? null, $evidence)) {
            $evidenceGaps[] = $this->issue('missing_meeting_minutes_evidence', "Concluded meeting {$key} lacks complete minutes Evidence.");
        }
        $meetingPrerequisitesComplete = count($conflicts) + count($meetingGaps) + count($authorityGaps) + count($evidenceGaps) === $meetingIssueCount;
        $agendaItems = [];
        $agendaKeys = [];

        foreach ($meeting['agenda_items'] ?? [] as $item) {
            $agendaItems[] = $this->resolveAgendaItem(
                $key,
                $item,
                $partners,
                $authority,
                $evidence,
                $decisionRules,
                $reservedMatters,
                $presentPartnerKeys,
                $presentWeight,
                $meetingPrerequisitesComplete,
                $policiesOperative,
                $lifecycle,
                $concludedAt,
                $asOf,
                $agendaKeys,
                $outcomeCounts,
                $conflicts,
                $meetingGaps,
                $authorityGaps,
                $evidenceGaps,
            );
        }

        if (in_array($lifecycle, [GovernanceMeetingLifecycleStatus::Convened, GovernanceMeetingLifecycleStatus::Deliberating, GovernanceMeetingLifecycleStatus::Concluded, GovernanceMeetingLifecycleStatus::Adjourned], true) && $agendaItems === []) {
            $meetingGaps[] = $this->issue('meeting_without_agenda_items', "Meeting {$key} has no recorded agenda item.");
        }

        return [
            ...$meeting,
            'lifecycle_status_label' => $lifecycle?->label() ?? 'Invalid',
            'attendance' => $attendance,
            'present_governance_weight' => $presentWeight,
            'agenda_items' => $agendaItems,
            'decision_record_candidate_count' => count(array_filter($agendaItems, static fn (array $item): bool => $item['decision_record_candidate'] !== null)),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $attendance
     * @param  array<string, array<string, mixed>>  $partners
     * @param  array<string, array<string, mixed>>  $evidence
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $meetingGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return list<array<string, mixed>>
     */
    private function resolveAttendance(string $meetingKey, array $attendance, array $partners, array $evidence, array &$conflicts, array &$meetingGaps, array &$evidenceGaps): array
    {
        $resolved = [];
        $recordedKeys = [];
        foreach ($attendance as $record) {
            $partnerKey = (string) ($record['identity_key'] ?? '');
            if (! isset($partners[$partnerKey]) || in_array($partnerKey, $recordedKeys, true) || ! in_array($record['status'] ?? null, ['present', 'absent'], true)) {
                $conflicts[] = $this->issue('invalid_meeting_attendance', "Meeting {$meetingKey} has unknown, duplicate, or invalid attendance.");
            }
            $recordedKeys[] = $partnerKey;
            if (! $this->hasEvidence($record['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_attendance_evidence', "Meeting {$meetingKey} lacks complete attendance Evidence for {$partnerKey}.");
            }
            $resolved[] = [
                ...$record,
                'identity_name' => $partners[$partnerKey]['name'] ?? null,
                'governance_weight' => $partners[$partnerKey]['governance_weight'] ?? null,
            ];
        }
        foreach (array_keys($partners) as $partnerKey) {
            if (! in_array($partnerKey, $recordedKeys, true)) {
                $meetingGaps[] = $this->issue('unrecorded_partner_attendance', "Meeting {$meetingKey} does not record attendance for {$partners[$partnerKey]['name']}.");
            }
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, array<string, mixed>>  $partners
     * @param  array<string, array<string, mixed>>  $authority
     * @param  array<string, array<string, mixed>>  $evidence
     * @param  array<string, mixed>  $decisionRules
     * @param  list<array<string, string>>  $reservedMatters
     * @param  list<string>  $presentPartnerKeys
     * @param  list<string>  $agendaKeys
     * @param  array<string, int>  $outcomeCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $meetingGaps
     * @param  list<array{code: string, message: string}>  $authorityGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array<string, mixed>
     */
    private function resolveAgendaItem(string $meetingKey, array $item, array $partners, array $authority, array $evidence, array $decisionRules, array $reservedMatters, array $presentPartnerKeys, int $presentWeight, bool $meetingPrerequisitesComplete, bool $policiesOperative, ?GovernanceMeetingLifecycleStatus $meetingLifecycle, ?Carbon $concludedAt, Carbon $asOf, array &$agendaKeys, array &$outcomeCounts, array &$conflicts, array &$meetingGaps, array &$authorityGaps, array &$evidenceGaps): array
    {
        $issueCount = count($conflicts) + count($meetingGaps) + count($authorityGaps) + count($evidenceGaps);
        $key = (string) ($item['key'] ?? '');
        $classification = $item['classification'] ?? null;
        if ($key === '' || in_array($key, $agendaKeys, true)) {
            $conflicts[] = $this->issue('invalid_agenda_item_key', "Meeting {$meetingKey} has a missing or duplicate agenda item key.");
        }
        $agendaKeys[] = $key;
        if (! in_array($classification, ['ordinary', 'reserved'], true)) {
            $conflicts[] = $this->issue('invalid_agenda_classification', "Agenda item {$key} has an invalid classification.");
        }
        $reservedMatterIndex = $this->indexByKey($reservedMatters);
        if ($classification === 'reserved' && ! isset($reservedMatterIndex[$item['reserved_matter_key'] ?? ''])) {
            $conflicts[] = $this->issue('unknown_reserved_matter', "Agenda item {$key} does not cite an exact constitutional Reserved Matter.");
        }
        if ($classification === 'ordinary' && ($item['reserved_matter_key'] ?? null) !== null) {
            $conflicts[] = $this->issue('ordinary_item_cites_reserved_matter', "Ordinary agenda item {$key} cites a Reserved Matter.");
        }

        $proposal = $item['proposal'] ?? null;
        if (! is_array($proposal) || ! isset($partners[$proposal['proposed_by_identity_key'] ?? ''])) {
            $meetingGaps[] = $this->issue('missing_governance_proposal', "Agenda item {$key} lacks an attributable Partner proposal.");
        } elseif (! $this->hasEvidence($proposal['evidence_record_key'] ?? null, $evidence)) {
            $evidenceGaps[] = $this->issue('missing_governance_proposal_evidence', "Agenda item {$key} lacks complete proposal Evidence.");
        }

        $disclosures = $item['disclosures'] ?? [];
        $disclosureIndex = [];
        foreach ($disclosures as $disclosure) {
            $partnerKey = (string) ($disclosure['identity_key'] ?? '');
            if (! isset($partners[$partnerKey]) || isset($disclosureIndex[$partnerKey]) || ! in_array($disclosure['status'] ?? null, ['no_conflict', 'conflict_disclosed'], true)) {
                $conflicts[] = $this->issue('invalid_conflict_disclosure', "Agenda item {$key} has an unknown, duplicate, or invalid conflict disclosure.");
            }
            if (($disclosure['recused'] ?? false) === true && empty($disclosure['reason'])) {
                $meetingGaps[] = $this->issue('recusal_without_reason', "Agenda item {$key} has an unexplained recusal by {$partnerKey}.");
            }
            if (! $this->hasEvidence($disclosure['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_disclosure_evidence', "Agenda item {$key} lacks disclosure Evidence for {$partnerKey}.");
            }
            $disclosureIndex[$partnerKey] = $disclosure;
        }
        foreach ($presentPartnerKeys as $partnerKey) {
            if (! isset($disclosureIndex[$partnerKey])) {
                $meetingGaps[] = $this->issue('missing_partner_disclosure', "Agenda item {$key} has no conflict declaration for {$partners[$partnerKey]['name']}.");
            }
        }

        $votes = $item['votes'] ?? [];
        $voteKeys = [];
        $voteWeights = ['for' => 0, 'against' => 0, 'abstain' => 0];
        foreach ($votes as $vote) {
            $partnerKey = (string) ($vote['identity_key'] ?? '');
            $choice = $vote['choice'] ?? null;
            if (! in_array($partnerKey, $presentPartnerKeys, true) || in_array($partnerKey, $voteKeys, true) || ! in_array($choice, ['for', 'against', 'abstain'], true)) {
                $conflicts[] = $this->issue('invalid_partner_vote', "Agenda item {$key} has an ineligible, duplicate, or invalid vote.");
            }
            if (($disclosureIndex[$partnerKey]['recused'] ?? false) === true) {
                $conflicts[] = $this->issue('recused_partner_voted', "Recused Partner {$partnerKey} voted on agenda item {$key}.");
            }
            if (! $this->hasEvidence($vote['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_vote_evidence', "Agenda item {$key} lacks vote Evidence for {$partnerKey}.");
            }
            $voteKeys[] = $partnerKey;
            if (isset($voteWeights[$choice]) && isset($partners[$partnerKey])) {
                $voteWeights[$choice] += (int) $partners[$partnerKey]['governance_weight'];
            }
        }
        foreach ($presentPartnerKeys as $partnerKey) {
            if (($disclosureIndex[$partnerKey]['recused'] ?? false) !== true && ! in_array($partnerKey, $voteKeys, true)) {
                $meetingGaps[] = $this->issue('missing_partner_vote', "Agenda item {$key} records neither a vote nor an abstention for {$partners[$partnerKey]['name']}.");
            }
        }

        $rule = is_string($classification) ? ($decisionRules[$classification] ?? []) : [];
        $quorumThreshold = $rule['quorum']['required_governance_weight'] ?? null;
        $approvalThreshold = $rule['approval']['required_governance_weight'] ?? null;
        $quorumMet = is_int($quorumThreshold) && $presentWeight >= $quorumThreshold;
        $authorityEntry = $authority[$item['authority_matrix_entry_key'] ?? ''] ?? null;
        $authorityResolved = $authorityEntry !== null
            && ($authorityEntry['grants_firm_authority'] ?? false) === true
            && array_diff($voteKeys, $authorityEntry['effective_holder_keys'] ?? []) === [];
        if ($authorityEntry === null || ! $authorityResolved) {
            $authorityGaps[] = $this->issue('unresolved_collective_authority', "Agenda item {$key} lacks effective collective authority for every recorded voter.");
        }

        $derivedOutcome = 'no_decision';
        if ($quorumMet && is_int($approvalThreshold)) {
            $derivedOutcome = match (true) {
                $voteWeights['for'] >= $approvalThreshold => 'adopted',
                $voteWeights['for'] === $voteWeights['against'] && ($decisionRules['deadlock']['state'] ?? null) !== 'resolved' => 'deadlock_unresolved',
                $voteWeights['for'] === $voteWeights['against'] => 'tied_pending_mechanism',
                default => 'rejected',
            };
        }
        $outcomeCounts[$derivedOutcome]++;
        if (($item['recorded_outcome'] ?? null) !== $derivedOutcome) {
            $conflicts[] = $this->issue('governance_outcome_mismatch', "Agenda item {$key} records an outcome inconsistent with its resolved vote.");
        }
        if (in_array($derivedOutcome, ['adopted', 'rejected', 'deadlock_unresolved', 'tied_pending_mechanism'], true) && ! $this->hasEvidence($item['outcome_evidence_record_key'] ?? null, $evidence)) {
            $evidenceGaps[] = $this->issue('missing_governance_outcome_evidence', "Agenda item {$key} lacks complete outcome Evidence.");
        }

        $issueFree = count($conflicts) + count($meetingGaps) + count($authorityGaps) + count($evidenceGaps) === $issueCount;
        $candidateEligible = $issueFree
            && $meetingPrerequisitesComplete
            && $policiesOperative
            && $meetingLifecycle === GovernanceMeetingLifecycleStatus::Concluded
            && $concludedAt !== null
            && ! $concludedAt->isAfter($asOf)
            && $derivedOutcome === 'adopted';
        $candidate = $candidateEligible ? [
            'source_type' => 'governance_meeting',
            'meeting_key' => $meetingKey,
            'agenda_item_key' => $key,
            'title' => $item['title'] ?? $key,
            'context' => ['type' => 'firm_governance'],
            'materiality' => $classification,
            'collective_authority' => [
                'authority_matrix_entry_key' => $item['authority_matrix_entry_key'],
                'participant_identity_keys' => $voteKeys,
            ],
            'vote_tally' => $voteWeights,
            'outcome' => 'approved',
            'decided_at' => $item['outcome_recorded_at'] ?? $concludedAt->toIso8601String(),
            'evidence_record_key' => $item['outcome_evidence_record_key'] ?? null,
            'canonical_decision_record_created' => false,
        ] : null;

        return [
            ...$item,
            'reserved_matter_label' => $reservedMatterIndex[$item['reserved_matter_key'] ?? '']['label'] ?? null,
            'quorum' => [
                'state' => $rule['quorum']['state'] ?? 'unresolved',
                'present_governance_weight' => $presentWeight,
                'required_governance_weight' => $quorumThreshold,
                'met' => $quorumMet,
            ],
            'vote_tally' => $voteWeights,
            'derived_outcome' => $derivedOutcome,
            'authority_resolved' => $authorityResolved,
            'decision_record_candidate' => $candidate,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function resolveGoverningPartners(ResolvedPartnership $partnership): array
    {
        return array_values(array_map(static fn (array $partner): array => [
            'key' => $partner['key'],
            'name' => $partner['name'],
            'partner_status' => $partner['partner_status'],
            'governance_weight' => $partner['governance_weight'],
        ], $partnership->formation['founding_partners'] ?? []));
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
                $conflicts[] = $this->issue('governance_policy_mismatch', "Required policy {$requirement['key']} {$requirement['version']} is missing or not current.");
            }
            $operative = $versionMatches && ($policy['current_status'] ?? null) === 'effective';
            if (($requirement['required_for_conclusion'] ?? false) === true && ! $operative) {
                $readinessGaps[] = $this->issue('governance_policy_not_effective', "{$requirement['title']} {$requirement['version']} is not Effective.");
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

    /**
     * @param  array<string, mixed>  $rules
     * @param  list<array{code: string, message: string}>  $readinessGaps
     * @param  list<array{code: string, message: string}>  $conflicts
     * @return array<string, mixed>
     */
    private function resolveDecisionRules(array $rules, array &$readinessGaps, array &$conflicts): array
    {
        foreach (['ordinary', 'reserved'] as $classification) {
            foreach (['quorum', 'approval'] as $ruleType) {
                $rule = $rules[$classification][$ruleType] ?? [];
                if (($rule['state'] ?? null) !== 'resolved') {
                    $readinessGaps[] = $this->issue("{$classification}_{$ruleType}_unresolved", ucfirst($classification)." matter {$ruleType} mechanics remain UNRESOLVED.");

                    continue;
                }
                $weight = $rule['required_governance_weight'] ?? null;
                if (! is_int($weight) || $weight < 1 || $weight > 100) {
                    $conflicts[] = $this->issue('invalid_governance_weight_threshold', ucfirst($classification)." matter {$ruleType} must use a governance-weight threshold from 1 through 100.");
                }
                if ($ruleType === 'approval' && ($rule['basis'] ?? null) !== 'total_governance_weight') {
                    $conflicts[] = $this->issue('invalid_approval_basis', ucfirst($classification).' matter approval must state its denominator explicitly.');
                }
            }
        }
        if (($rules['deadlock']['state'] ?? null) !== 'resolved') {
            $readinessGaps[] = $this->issue('deadlock_mechanism_unresolved', 'The 50/50 governance deadlock mechanism remains UNRESOLVED and requires counsel review.');
        } elseif (empty($rules['deadlock']['mechanism'])) {
            $conflicts[] = $this->issue('missing_deadlock_mechanism', 'A resolved deadlock rule must identify its adopted mechanism.');
        }

        return $rules;
    }

    /**
     * @param  list<array<string, string>>  $catalog
     * @param  list<string>  $constitutionalMatters
     * @param  list<array{code: string, message: string}>  $conflicts
     */
    private function validateReservedMatterCatalog(array $catalog, array $constitutionalMatters, array &$conflicts): void
    {
        $labels = array_column($catalog, 'label');
        if (array_diff($labels, $constitutionalMatters) !== [] || array_diff($constitutionalMatters, $labels) !== []) {
            $conflicts[] = $this->issue('reserved_matter_catalog_mismatch', 'The meeting catalog does not exactly match Resolved Partnership Reserved Matters.');
        }
        if (count(array_unique(array_column($catalog, 'key'))) !== count($catalog)) {
            $conflicts[] = $this->issue('duplicate_reserved_matter_key', 'The Reserved Matter catalog contains duplicate keys.');
        }
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
