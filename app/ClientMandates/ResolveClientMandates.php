<?php

namespace App\ClientMandates;

use App\AuthorityMatrix\ResolvedAuthorityMatrix;
use App\Engagements\ResolvedEngagements;
use DateTimeImmutable;
use Illuminate\Support\Carbon;

final class ResolveClientMandates
{
    public function handle(ClientMandateDefinition $definition, ResolvedEngagements $engagements, ResolvedAuthorityMatrix $authorityMatrix, ?DateTimeImmutable $asOf = null): ResolvedClientMandates
    {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $mandateGaps */
        $mandateGaps = [];
        /** @var list<array{code: string, message: string}> $authorityGaps */
        $authorityGaps = [];
        /** @var list<array{code: string, message: string}> $approvalGaps */
        $approvalGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        $asOf = Carbon::instance($asOf ?? new DateTimeImmutable);
        $engagementIndex = $this->indexByKey($engagements->engagements);
        $authorityIndex = $this->indexByKey($authorityMatrix->entries);
        $evidenceResult = $this->evidenceKeys($definition->evidenceRecords);
        $evidenceKeys = $evidenceResult['keys'];
        $conflicts = [...$conflicts, ...$evidenceResult['conflicts']];
        $evidenceGaps = [...$evidenceGaps, ...$evidenceResult['gaps']];
        $resolved = [];
        $permitted = [];
        $keys = [];

        foreach ($definition->actionRequests as $request) {
            $key = (string) ($request['key'] ?? '');
            $issuesBefore = count($conflicts) + count($mandateGaps) + count($authorityGaps) + count($approvalGaps) + count($evidenceGaps);
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_action_request_key', 'A Client action request has a missing or duplicate key.');
            }
            $keys[] = $key;
            $engagement = $engagementIndex[(string) ($request['engagement_key'] ?? '')] ?? null;
            if ($engagement === null || ($engagement['may_perform_client_work'] ?? false) !== true) {
                $mandateGaps[] = $this->issue('engagement_not_open_for_action', "Action request {$key} does not reference an Engagement open for Client work.");
            }
            $mandate = is_array($engagement['client_mandate'] ?? null) ? $engagement['client_mandate'] : null;
            $actionKey = (string) ($request['action_key'] ?? '');
            $environment = (string) ($request['environment'] ?? '');
            $system = (string) ($request['system'] ?? '');
            if ($mandate === null || ! $this->contains($mandate['permitted_actions'] ?? [], $actionKey) || ! $this->contains($mandate['environments'] ?? [], $environment) || ! $this->contains($mandate['systems'] ?? [], $system)) {
                $mandateGaps[] = $this->issue('client_mandate_does_not_cover_action', "Client Mandate does not cover action request {$key}.");
            } elseif (! $this->current($mandate, $asOf)) {
                $mandateGaps[] = $this->issue('client_mandate_not_current', "Client Mandate for action {$key} is not current.");
            }
            $authority = $authorityIndex[(string) ($request['authority_entry_key'] ?? '')] ?? null;
            if ($authority === null || ($authority['grants_firm_authority'] ?? false) !== true || ! in_array((string) ($request['actor_identity_key'] ?? ''), $authority['effective_holder_keys'] ?? [], true) || ($authority['action_key'] ?? null) !== $actionKey) {
                $authorityGaps[] = $this->issue('firm_authority_does_not_cover_action', "Firm Authority does not cover action request {$key}.");
            }
            $approval = is_array($request['specific_approval'] ?? null) ? $request['specific_approval'] : null;
            if ($approval === null || ($approval['outcome'] ?? null) !== 'approved' || empty($approval['approved_by']) || empty($approval['approved_at'])) {
                $approvalGaps[] = $this->issue('specific_approval_missing', "Action request {$key} lacks separate Specific Approval.");
            } elseif (! $this->hasEvidence($approval['evidence_record_key'] ?? null, $evidenceKeys)) {
                $evidenceGaps[] = $this->issue('specific_approval_evidence_missing', "Specific Approval for action {$key} lacks Evidence.");
            }
            if (! $this->hasEvidence($request['evidence_record_key'] ?? null, $evidenceKeys)) {
                $evidenceGaps[] = $this->issue('action_request_evidence_missing', "Action request {$key} lacks Evidence.");
            }
            $allowed = $issuesBefore === count($conflicts) + count($mandateGaps) + count($authorityGaps) + count($approvalGaps) + count($evidenceGaps);
            $resolved[] = [...$request, 'permitted' => $allowed];
            if ($allowed) {
                $permitted[] = [...$request, 'permitted' => true];
            }
        }

        return new ResolvedClientMandates($definition->schemaVersion, $definition->requirements, $resolved, $permitted, $definition->evidenceRecords, $conflicts, $mandateGaps, $authorityGaps, $approvalGaps, $evidenceGaps);
    }

    /** @param array<string, mixed> $mandate */
    private function current(array $mandate, Carbon $asOf): bool
    {
        try {
            $granted = isset($mandate['granted_at']) ? Carbon::parse($mandate['granted_at']) : null;
            $until = isset($mandate['valid_until']) ? Carbon::parse($mandate['valid_until']) : null;
        } catch (\Throwable) {
            return false;
        }

        return $granted !== null && $until !== null && $granted->lessThanOrEqualTo($asOf) && $until->greaterThan($asOf);
    }

    private function contains(mixed $values, string $value): bool
    {
        return is_array($values) && in_array($value, $values, true);
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

    /**
     * @param  list<array<string, mixed>>  $records
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
                $conflicts[] = $this->issue('invalid_client_mandate_evidence_key', 'Client Mandate Evidence has a missing or duplicate key.');
            } elseif (empty($record['record_type']) || empty($record['subject']) || empty($record['actor']) || empty($record['recorded_at']) || empty($record['source']) || empty($record['reason']) || empty($record['state'])) {
                $gaps[] = $this->issue('incomplete_client_mandate_evidence', "Evidence {$key} is incomplete.");
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
