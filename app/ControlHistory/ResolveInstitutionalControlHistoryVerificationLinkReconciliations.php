<?php

namespace App\ControlHistory;

use DateTimeImmutable;
use Throwable;

final class ResolveInstitutionalControlHistoryVerificationLinkReconciliations
{
    public function handle(
        InstitutionalControlHistoryVerificationLinkReconciliationDefinition $definition,
        ResolvedInstitutionalControlHistoryVerificationEvidenceLinks $links,
        ResolvedInstitutionalControlHistoryAnchorVerification $verification,
    ): ResolvedInstitutionalControlHistoryVerificationLinkReconciliations {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $reconciliationGaps */
        $reconciliationGaps = [];
        /** @var list<array<string, mixed>> $reconciliations */
        $reconciliations = [];
        $seenKeys = [];
        $linksByKey = [];
        foreach ($links->links as $link) {
            $linksByKey[(string) ($link['key'] ?? '')] = $link;
        }

        if ($definition->source !== $links->linkRegistryKey) {
            $conflicts[] = $this->issue('reconciliation_source_mismatch', 'Reconciliations do not identify the resolved verification link registry.');
        }
        foreach ($links->conflicts as $conflict) {
            $reconciliationGaps[] = $this->issue('link_registry_conflict', $conflict['code'].': '.$conflict['message']);
        }
        foreach ($links->linkGaps as $gap) {
            $reconciliationGaps[] = $this->issue('link_registry_gap', $gap['code'].': '.$gap['message']);
        }

        foreach ($definition->reconciliations as $record) {
            $key = (string) ($record['key'] ?? '');
            $linkKey = (string) ($record['link_key'] ?? '');
            if ($key === '' || isset($seenKeys[$key])) {
                $conflicts[] = $this->issue('invalid_reconciliation_key', 'Every link reconciliation requires a unique key.');
            }
            $seenKeys[$key] = true;

            foreach (['link_key', 'reconciled_by', 'reconciled_at', 'basis', 'evidence_record_key'] as $field) {
                $gap = $this->missingField($record, $field, $key);
                if ($gap !== null) {
                    $reconciliationGaps[] = $gap;
                }
            }
            if (! $this->validDate($record['reconciled_at'] ?? null)) {
                $reconciliationGaps[] = $this->issue('invalid_reconciliation_time', "Reconciliation {$key} lacks a valid reconciled_at timestamp.");
            }

            $link = $linksByKey[$linkKey] ?? null;
            if ($link === null) {
                $reconciliationGaps[] = $this->issue('reconciliation_without_link', "Reconciliation {$key} does not cite a known verification link.");
            }

            $comparisons = [
                'verification_key' => [$record['observed_verification_key'] ?? null, $link['verification_key'] ?? null],
                'verification_status' => [$record['observed_verification_status'] ?? null, $link['verification_status'] ?? null],
                'resolved_history_anchor' => [$record['observed_resolved_history_anchor'] ?? null, $link['resolved_history_anchor'] ?? null],
                'supplied_history_anchor' => [$record['observed_supplied_history_anchor'] ?? null, $link['supplied_history_anchor'] ?? null],
            ];
            $matches = $link !== null;
            foreach ($comparisons as $field => [$observed, $expected]) {
                if ($observed !== $expected) {
                    $matches = false;
                    $reconciliationGaps[] = $this->issue('reconciliation_snapshot_mismatch', "Reconciliation {$key} does not match {$field} from link {$linkKey}.");
                }
            }
            if (($record['reconciled'] ?? null) !== $matches) {
                $reconciliationGaps[] = $this->issue('reconciliation_outcome_mismatch', "Reconciliation {$key} outcome does not match its observed snapshot comparison.");
            }

            $reconciliations[] = [
                'key' => $key,
                'link_key' => $linkKey,
                'observed_verification_key' => $record['observed_verification_key'] ?? null,
                'observed_verification_status' => $record['observed_verification_status'] ?? null,
                'observed_resolved_history_anchor' => $record['observed_resolved_history_anchor'] ?? null,
                'observed_supplied_history_anchor' => $record['observed_supplied_history_anchor'] ?? null,
                'reconciled' => $record['reconciled'] ?? null,
                'reconciled_by' => $record['reconciled_by'] ?? null,
                'reconciled_at' => $record['reconciled_at'] ?? null,
                'basis' => $record['basis'] ?? null,
                'evidence_record_key' => $record['evidence_record_key'] ?? null,
            ];
        }

        return new ResolvedInstitutionalControlHistoryVerificationLinkReconciliations(
            schemaVersion: $definition->schemaVersion,
            reconciliationKey: $definition->reconciliationKey,
            source: $definition->source,
            reconciliations: $reconciliations,
            conflicts: $conflicts,
            reconciliationGaps: $reconciliationGaps,
        );
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array{code: string, message: string}|null
     */
    private function missingField(array $record, string $field, string $key): ?array
    {
        if (! is_string($record[$field] ?? null) || trim((string) $record[$field]) === '') {
            return $this->issue('missing_reconciliation_'.$field, "Reconciliation {$key} lacks {$field}.");
        }

        return null;
    }

    private function validDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }
        try {
            new DateTimeImmutable($value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
