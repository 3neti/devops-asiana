<?php

namespace App\ControlDecisions;

use App\ControlClosures\ResolvedControlReviewClosureEligibility;

final class ResolveControlReviewClosureDecisions
{
    public function handle(
        ControlReviewClosureDecisionDefinition $definition,
        ResolvedControlReviewClosureEligibility $eligibility,
    ): ResolvedControlReviewClosureDecisions {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $decisionGaps */
        $decisionGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        /** @var list<string> $keys */
        $keys = [];
        /** @var list<string> $targets */
        $targets = [];
        /** @var list<array<string, mixed>> $resolved */
        $resolved = [];
        $eligibilityReviews = $this->indexByKey($eligibility->eligibilityReviews);

        foreach ($definition->decisions as $decision) {
            $key = (string) ($decision['key'] ?? '');
            $issuesBefore = count($conflicts) + count($decisionGaps) + count($evidenceGaps);
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_closure_decision_key', 'Closure Decision has a missing or duplicate key.');
            }
            $keys[] = $key;

            $eligibilityKey = (string) ($decision['eligibility_review_key'] ?? '');
            $eligibilityReview = $eligibilityReviews[$eligibilityKey] ?? null;
            if (! is_array($eligibilityReview)) {
                $conflicts[] = $this->issue('closure_eligibility_not_found', "Closure Decision {$key} references an unknown eligibility review.");
            }
            if (in_array($eligibilityKey, $targets, true)) {
                $conflicts[] = $this->issue('duplicate_closure_decision_target', "Closure Decision {$key} duplicates a decision for eligibility review {$eligibilityKey}.");
            }
            $targets[] = $eligibilityKey;

            $outcome = (string) ($decision['decision'] ?? '');
            if (! in_array($outcome, ['closed', 'deferred', 'rejected'], true)) {
                $conflicts[] = $this->issue('invalid_closure_decision_outcome', "Closure Decision {$key} has an invalid outcome.");
            }
            if ($outcome === 'closed' && (! is_array($eligibilityReview) || ($eligibilityReview['closure_eligible'] ?? false) !== true)) {
                $conflicts[] = $this->issue('closure_decision_without_eligibility', "Closure Decision {$key} cannot close an action that is not eligible.");
            }
            foreach (['decided_by', 'decided_at', 'authority_basis', 'reason'] as $field) {
                if (empty($decision[$field])) {
                    $decisionGaps[] = $this->issue('incomplete_closure_decision', "Closure Decision {$key} lacks {$field}.");
                }
            }
            if (empty($decision['evidence_record_key'])) {
                $evidenceGaps[] = $this->issue('missing_closure_decision_evidence', "Closure Decision {$key} lacks its Evidence record reference.");
            }

            if ($issuesBefore === count($conflicts) + count($decisionGaps) + count($evidenceGaps)) {
                $resolved[] = [...$decision, 'decision_resolved' => true, 'closure_admitted' => $outcome === 'closed'];
            }
        }

        return new ResolvedControlReviewClosureDecisions(
            schemaVersion: $definition->schemaVersion,
            requirements: $definition->requirements,
            decisions: $definition->decisions,
            resolvedDecisions: $resolved,
            conflicts: $conflicts,
            decisionGaps: $decisionGaps,
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
