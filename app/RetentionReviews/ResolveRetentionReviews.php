<?php

namespace App\RetentionReviews;

use App\EvidenceCustody\ResolvedEvidenceCustody;
use App\Policies\PolicyExceptionStatus;
use App\Policies\ResolvedPolicyRegistry;

final class ResolveRetentionReviews
{
    public function handle(
        RetentionReviewDefinition $definition,
        ResolvedEvidenceCustody $custody,
        ResolvedPolicyRegistry $policies,
    ): ResolvedRetentionReviews {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $reviewGaps */
        $reviewGaps = [];
        /** @var list<array{code: string, message: string}> $exceptionGaps */
        $exceptionGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        /** @var list<string> $reviewKeys */
        $reviewKeys = [];
        /** @var list<array<string, mixed>> $resolved */
        $resolved = [];
        $custodyRecords = $this->indexByKey($custody->records);
        $evidenceKeys = $this->evidenceKeys($custody->records);
        $exceptions = $this->approvedExceptions($policies);

        foreach ($definition->reviews as $review) {
            $key = (string) ($review['key'] ?? '');
            $issuesBefore = count($conflicts) + count($reviewGaps) + count($exceptionGaps) + count($evidenceGaps);
            if ($key === '' || in_array($key, $reviewKeys, true)) {
                $conflicts[] = $this->issue('invalid_retention_review_key', 'Retention Review has a missing or duplicate key.');
            }
            $reviewKeys[] = $key;

            $custodyKey = (string) ($review['custody_key'] ?? '');
            if (! isset($custodyRecords[$custodyKey])) {
                $reviewGaps[] = $this->issue('retention_review_custody_not_found', "Retention Review {$key} references unknown Evidence Custody.");
            }

            $outcome = (string) ($review['outcome'] ?? '');
            if (! in_array($outcome, ['compliant', 'exception_required', 'disposition_due'], true)) {
                $conflicts[] = $this->issue('invalid_retention_review_outcome', "Retention Review {$key} has an invalid outcome.");
            }
            if (empty($review['reviewer']) || empty($review['reviewed_at']) || empty($review['basis'])) {
                $reviewGaps[] = $this->issue('incomplete_retention_review', "Retention Review {$key} lacks reviewer, review time, or basis.");
            }

            $evidenceKey = (string) ($review['evidence_record_key'] ?? '');
            if (! isset($evidenceKeys[$evidenceKey])) {
                $evidenceGaps[] = $this->issue('retention_review_evidence_not_indexed', "Retention Review {$key} references Evidence not present in custody records.");
            }

            $exceptionKey = (string) ($review['policy_exception_key'] ?? '');
            if ($outcome === 'exception_required') {
                if ($exceptionKey === '' || ! isset($exceptions[$exceptionKey])) {
                    $exceptionGaps[] = $this->issue('retention_review_exception_not_approved', "Retention Review {$key} requires an approved or active Policy Exception.");
                }
            } elseif ($exceptionKey !== '') {
                $conflicts[] = $this->issue('unexpected_retention_review_exception', "Retention Review {$key} names a Policy Exception without requiring one.");
            }

            if ($issuesBefore === count($conflicts) + count($reviewGaps) + count($exceptionGaps) + count($evidenceGaps)) {
                $resolved[] = [...$review, 'review_resolved' => true];
            }
        }

        return new ResolvedRetentionReviews(
            schemaVersion: $definition->schemaVersion,
            requirements: $definition->requirements,
            reviews: $definition->reviews,
            resolvedReviews: $resolved,
            conflicts: $conflicts,
            reviewGaps: $reviewGaps,
            exceptionGaps: $exceptionGaps,
            evidenceGaps: $evidenceGaps,
        );
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
     * @return array<string, true>
     */
    private function evidenceKeys(array $records): array
    {
        $keys = [];
        foreach ($records as $record) {
            if (is_string($record['evidence_key'] ?? null)) {
                $keys[$record['evidence_key']] = true;
            }
        }

        return $keys;
    }

    /** @return array<string, true> */
    private function approvedExceptions(ResolvedPolicyRegistry $policies): array
    {
        $approved = [];
        foreach ($policies->exceptions as $exception) {
            $status = PolicyExceptionStatus::tryFrom((string) ($exception['status'] ?? ''));
            if (is_string($exception['key'] ?? null) && in_array($status, [PolicyExceptionStatus::Approved, PolicyExceptionStatus::Active], true)) {
                $approved[$exception['key']] = true;
            }
        }

        return $approved;
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
