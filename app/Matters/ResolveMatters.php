<?php

namespace App\Matters;

use App\Engagements\ResolvedEngagements;
use DateTimeImmutable;
use Illuminate\Support\Carbon;

final class ResolveMatters
{
    public function handle(MatterDefinition $definition, ResolvedEngagements $engagements, ?DateTimeImmutable $asOf = null): ResolvedMatters
    {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $engagementGaps */
        $engagementGaps = [];
        /** @var list<array{code: string, message: string}> $responsibilityGaps */
        $responsibilityGaps = [];
        /** @var list<array{code: string, message: string}> $scopeGaps */
        $scopeGaps = [];
        /** @var list<array{code: string, message: string}> $riskGaps */
        $riskGaps = [];
        /** @var list<array{code: string, message: string}> $escalationGaps */
        $escalationGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        $asOf = Carbon::instance($asOf ?? new DateTimeImmutable);
        $engagementIndex = $this->indexByKey($engagements->engagements);
        $evidenceResult = $this->evidenceKeys($definition->evidenceRecords);
        $evidenceKeys = $evidenceResult['keys'];
        $conflicts = [...$conflicts, ...$evidenceResult['conflicts']];
        $evidenceGaps = [...$evidenceGaps, ...$evidenceResult['gaps']];
        $matterKeys = [];
        $resolved = [];
        $projections = [];

        foreach ($definition->matters as $matter) {
            $key = (string) ($matter['key'] ?? '');
            $before = count($conflicts) + count($engagementGaps) + count($responsibilityGaps) + count($scopeGaps) + count($riskGaps) + count($escalationGaps) + count($evidenceGaps);
            if ($key === '' || in_array($key, $matterKeys, true)) {
                $conflicts[] = $this->issue('invalid_matter_key', 'A Matter has a missing or duplicate key.');
            }
            $matterKeys[] = $key;
            $engagement = $engagementIndex[(string) ($matter['engagement_key'] ?? '')] ?? null;
            if ($engagement === null) {
                $engagementGaps[] = $this->issue('matter_engagement_missing', "Matter {$key} does not reference a known Engagement.");
            } elseif (($engagement['may_perform_client_work'] ?? false) !== true) {
                $engagementGaps[] = $this->issue('matter_engagement_not_open', "Matter {$key} is not inside an Engagement open for Client work.");
            }
            $responsiblePartnerKey = (string) ($matter['responsible_partner_key'] ?? '');
            $engagementPartnerKey = (string) ($engagement['responsible_partner']['partner_key'] ?? '');
            if ($responsiblePartnerKey === '') {
                $responsibilityGaps[] = $this->issue('matter_responsible_partner_missing', "Matter {$key} has no Responsible Partner.");
            } elseif ($engagementPartnerKey === '' || $responsiblePartnerKey !== $engagementPartnerKey) {
                $responsibilityGaps[] = $this->issue('matter_responsible_partner_mismatch', "Matter {$key} does not preserve the Engagement's singular Responsible Partner.");
            }
            $scope = is_array($matter['scope'] ?? null) ? $matter['scope'] : [];
            if (empty($scope['purpose']) || empty($scope['work_boundary']) || empty($scope['deliverables']) || empty($scope['exclusions'])) {
                $scopeGaps[] = $this->issue('matter_scope_incomplete', "Matter {$key} lacks purpose, work boundary, deliverables, or exclusions.");
            }
            $risk = is_array($matter['risk'] ?? null) ? $matter['risk'] : [];
            if (empty($risk['classification']) || empty($risk['owner_partner_key']) || empty($risk['acceptance']['outcome']) || ($risk['acceptance']['outcome'] ?? null) !== 'accepted' || empty($risk['acceptance']['accepted_at']) || empty($risk['acceptance']['authority_basis'])) {
                $riskGaps[] = $this->issue('matter_risk_acceptance_incomplete', "Matter {$key} lacks explicit risk classification, ownership, or acceptance.");
            }
            $escalation = is_array($matter['escalation'] ?? null) ? $matter['escalation'] : [];
            if (empty($escalation['contacts']) || empty($escalation['triggers']) || empty($escalation['response_target'])) {
                $escalationGaps[] = $this->issue('matter_escalation_incomplete', "Matter {$key} lacks escalation contacts, triggers, or response target.");
            }
            if (! $this->hasEvidence($matter['evidence_record_key'] ?? null, $evidenceKeys)) {
                $evidenceGaps[] = $this->issue('matter_evidence_missing', "Matter {$key} lacks Evidence.");
            }
            $valid = $before === count($conflicts) + count($engagementGaps) + count($responsibilityGaps) + count($scopeGaps) + count($riskGaps) + count($escalationGaps) + count($evidenceGaps);
            $active = ($matter['lifecycle_status'] ?? null) === 'active';
            if ($active && ! $valid) {
                $conflicts[] = $this->issue('active_matter_without_complete_accountability', "Matter {$key} is Active without complete accountability gates.");
            }
            $projection = [
                ...$matter,
                'engagement_title' => $engagement['title'] ?? null,
                'client_name' => $engagement['client_name'] ?? null,
                'responsible_partner' => $engagement['responsible_partner'] ?? null,
                'may_perform_matter_work' => $active && $valid && ($engagement['may_perform_client_work'] ?? false) === true,
            ];
            $resolved[] = $projection;
            if ($projection['may_perform_matter_work']) {
                $projections[] = $projection;
            }
        }

        return new ResolvedMatters($definition->schemaVersion, $definition->requirements, $resolved, $projections, $definition->evidenceRecords, $conflicts, $engagementGaps, $responsibilityGaps, $scopeGaps, $riskGaps, $escalationGaps, $evidenceGaps);
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

    /** @param list<array<string, mixed>> $records
     * @return array{keys: list<string>, conflicts: list<array{code: string, message: string}>, gaps: list<array{code: string, message: string}>}
     */
    private function evidenceKeys(array $records): array
    {
        $keys = [];
        $conflicts = [];
        $gaps = [];
        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? '');
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_matter_evidence_key', 'Matter Evidence has a missing or duplicate key.');
            } elseif (empty($record['record_type']) || empty($record['subject']) || empty($record['actor']) || empty($record['recorded_at']) || empty($record['source']) || empty($record['reason']) || empty($record['state'])) {
                $gaps[] = $this->issue('incomplete_matter_evidence', "Evidence {$key} is incomplete.");
            } else {
                $keys[] = $key;
            }
        }

        return compact('keys', 'conflicts', 'gaps');
    }

    /** @param list<string> $evidenceKeys */
    private function hasEvidence(mixed $key, array $evidenceKeys): bool
    {
        return is_string($key) && in_array($key, $evidenceKeys, true);
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
