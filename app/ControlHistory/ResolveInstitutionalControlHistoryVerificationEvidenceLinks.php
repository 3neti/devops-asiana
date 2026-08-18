<?php

namespace App\ControlHistory;

use DateTimeImmutable;
use Throwable;

final class ResolveInstitutionalControlHistoryVerificationEvidenceLinks
{
    public function handle(
        InstitutionalControlHistoryVerificationEvidenceLinkDefinition $definition,
        ResolvedInstitutionalControlHistoryAnchorVerification $verification,
    ): ResolvedInstitutionalControlHistoryVerificationEvidenceLinks {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $linkGaps */
        $linkGaps = [];
        /** @var list<array<string, mixed>> $links */
        $links = [];
        $seenKeys = [];

        if ($definition->source !== $verification->verificationKey) {
            $conflicts[] = $this->issue('link_source_mismatch', 'Evidence links do not identify the resolved anchor verification projection.');
        }

        foreach ($definition->links as $record) {
            $key = (string) ($record['key'] ?? '');
            if ($key === '' || isset($seenKeys[$key])) {
                $conflicts[] = $this->issue('invalid_link_key', 'Every verification Evidence link requires a unique key.');
            }
            $seenKeys[$key] = true;

            foreach (['artifact_key', 'evidence_record_key', 'linked_by', 'linked_at', 'reason'] as $field) {
                $gap = $this->missingField($record, $field, $key);
                if ($gap !== null) {
                    $linkGaps[] = $gap;
                }
            }

            if (($record['verification_key'] ?? null) !== $verification->verificationKey) {
                $linkGaps[] = $this->issue('verification_snapshot_key_mismatch', "Link {$key} does not cite the exact verification snapshot.");
            }
            if (($record['verification_status'] ?? null) !== $verification->toArray()['status']) {
                $linkGaps[] = $this->issue('verification_snapshot_status_mismatch', "Link {$key} does not preserve the verification status.");
            }
            if (($record['resolved_history_anchor'] ?? null) !== $verification->resolvedHistoryAnchor) {
                $linkGaps[] = $this->issue('verification_snapshot_anchor_mismatch', "Link {$key} does not preserve the resolved history anchor.");
            }
            if (($record['supplied_history_anchor'] ?? null) !== $verification->suppliedHistoryAnchor) {
                $linkGaps[] = $this->issue('verification_snapshot_supplied_anchor_mismatch', "Link {$key} does not preserve the supplied history anchor.");
            }
            if (! $this->validDate($record['linked_at'] ?? null)) {
                $linkGaps[] = $this->issue('invalid_link_time', "Link {$key} lacks a valid linked_at timestamp.");
            }
            if ($verification->toArray()['status'] !== 'verified') {
                $linkGaps[] = $this->issue('link_to_unverified_comparison', "Link {$key} references verification status {$verification->toArray()['status']}.");
            }

            $links[] = [
                'key' => $key,
                'verification_key' => $record['verification_key'] ?? null,
                'verification_status' => $record['verification_status'] ?? null,
                'resolved_history_anchor' => $record['resolved_history_anchor'] ?? null,
                'supplied_history_anchor' => $record['supplied_history_anchor'] ?? null,
                'artifact_key' => $record['artifact_key'] ?? null,
                'evidence_record_key' => $record['evidence_record_key'] ?? null,
                'linked_by' => $record['linked_by'] ?? null,
                'linked_at' => $record['linked_at'] ?? null,
                'reason' => $record['reason'] ?? null,
            ];
        }

        return new ResolvedInstitutionalControlHistoryVerificationEvidenceLinks(
            schemaVersion: $definition->schemaVersion,
            linkRegistryKey: $definition->linkRegistryKey,
            source: $definition->source,
            links: $links,
            conflicts: $conflicts,
            linkGaps: $linkGaps,
        );
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array{code: string, message: string}|null
     */
    private function missingField(array $record, string $field, string $key): ?array
    {
        if (! is_string($record[$field] ?? null) || trim($record[$field]) === '') {
            return $this->issue('missing_link_'.$field, "Link {$key} lacks {$field}.");
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
