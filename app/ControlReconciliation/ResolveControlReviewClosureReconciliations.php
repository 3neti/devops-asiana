<?php

namespace App\ControlReconciliation;

use App\ControlClosures\ResolvedControlReviewClosureEligibility;
use App\ControlDecisions\ResolvedControlReviewClosureDecisions;

final class ResolveControlReviewClosureReconciliations
{
    public function handle(
        ControlReviewClosureReconciliationDefinition $definition,
        ResolvedControlReviewClosureDecisions $decisions,
        ResolvedControlReviewClosureEligibility $eligibility,
    ): ResolvedControlReviewClosureReconciliations {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $reconciliationGaps */
        $reconciliationGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        /** @var list<string> $keys */
        $keys = [];
        /** @var list<array<string, mixed>> $resolved */
        $resolved = [];
        $decisionRecords = $this->indexByKey($decisions->resolvedDecisions);
        $eligibilityRecords = $this->indexByKey($eligibility->eligibilityReviews);

        foreach ($definition->reconciliations as $record) {
            $key = (string) ($record['key'] ?? '');
            $issuesBefore = count($conflicts) + count($reconciliationGaps) + count($evidenceGaps);
            $conflictsBefore = count($conflicts);
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_closure_reconciliation_key', 'Closure Reconciliation has a missing or duplicate key.');
            }
            $keys[] = $key;

            $decisionKey = (string) ($record['decision_key'] ?? '');
            $decision = $decisionRecords[$decisionKey] ?? null;
            if (! is_array($decision)) {
                $conflicts[] = $this->issue('reconciliation_decision_not_admitted', "Closure Reconciliation {$key} references an unknown admitted decision.");
            }
            $eligibilityKey = is_array($decision) ? (string) ($decision['eligibility_review_key'] ?? '') : '';
            $eligibilityReview = $eligibilityRecords[$eligibilityKey] ?? null;
            if (! is_array($eligibilityReview)) {
                $conflicts[] = $this->issue('reconciliation_eligibility_not_found', "Closure Reconciliation {$key} cannot resolve its eligibility basis.");
            }

            $downstreamState = (string) ($record['downstream_state'] ?? '');
            if (! in_array($downstreamState, ['open', 'closed', 'unknown'], true)) {
                $conflicts[] = $this->issue('invalid_reconciliation_downstream_state', "Closure Reconciliation {$key} has an invalid downstream state.");
            }
            if (is_array($decision)) {
                $expected = ($decision['closure_admitted'] ?? false) === true ? 'closed' : 'open';
                if ($downstreamState !== $expected) {
                    $reconciliationGaps[] = $this->issue('closure_state_discrepancy', "Closure Reconciliation {$key} reports downstream {$downstreamState} while the admitted decision expects {$expected}.");
                }
            }
            foreach (['reconciled_by', 'reconciled_at', 'basis'] as $field) {
                if (empty($record[$field])) {
                    $reconciliationGaps[] = $this->issue('incomplete_closure_reconciliation', "Closure Reconciliation {$key} lacks {$field}.");
                }
            }
            if (empty($record['evidence_record_key'])) {
                $evidenceGaps[] = $this->issue('missing_closure_reconciliation_evidence', "Closure Reconciliation {$key} lacks its Evidence record reference.");
            }

            $resolved[] = [
                ...$record,
                'reconciled' => $issuesBefore === count($conflicts) + count($reconciliationGaps) + count($evidenceGaps)
                    && $conflictsBefore === count($conflicts),
                'source_mutated' => false,
            ];
        }

        return new ResolvedControlReviewClosureReconciliations(
            schemaVersion: $definition->schemaVersion,
            requirements: $definition->requirements,
            reconciliations: $definition->reconciliations,
            resolvedReconciliations: $resolved,
            conflicts: $conflicts,
            reconciliationGaps: $reconciliationGaps,
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

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
