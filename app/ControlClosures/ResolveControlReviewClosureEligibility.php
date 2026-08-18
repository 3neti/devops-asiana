<?php

namespace App\ControlClosures;

use App\ControlActions\ResolvedControlReviewActions;
use App\ControlOutcomes\ResolvedControlReviewActionOutcomes;
use DateTimeImmutable;
use Throwable;

final class ResolveControlReviewClosureEligibility
{
    public function handle(
        ControlReviewClosureEligibilityDefinition $definition,
        ResolvedControlReviewActions $actions,
        ResolvedControlReviewActionOutcomes $outcomes,
    ): ResolvedControlReviewClosureEligibility {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $eligibilityGaps */
        $eligibilityGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        /** @var list<string> $keys */
        $keys = [];
        /** @var list<array<string, mixed>> $eligibilityReviews */
        $eligibilityReviews = [];
        $actionRecords = $this->indexByKey($actions->resolvedActions);
        $outcomeRecords = $this->outcomesByAction($outcomes->resolvedOutcomes);

        foreach ($definition->reviews as $review) {
            $key = (string) ($review['key'] ?? '');
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_closure_eligibility_key', 'Closure Eligibility Review has a missing or duplicate key.');
            }
            $keys[] = $key;
            $actionKey = (string) ($review['action_key'] ?? '');
            $action = $actionRecords[$actionKey] ?? null;
            $gapsBefore = count($eligibilityGaps) + count($evidenceGaps);
            $conflictsBefore = count($conflicts);
            if (! is_array($action)) {
                $conflicts[] = $this->issue('closure_action_not_admitted', "Closure Eligibility Review {$key} references an unknown admitted Action.");
            }

            $actionOutcomes = $outcomeRecords[$actionKey] ?? [];
            $completion = $this->findOutcome($actionOutcomes, 'completion_claim');
            $verification = $this->findOutcome($actionOutcomes, 'verification_reference');
            if ($completion === null) {
                $eligibilityGaps[] = $this->issue('missing_completion_claim', "Closure Eligibility Review {$key} has no completion claim.");
            }
            if ($verification === null || ($verification['verification_outcome'] ?? null) !== 'verified') {
                $eligibilityGaps[] = $this->issue('missing_independent_verification', "Closure Eligibility Review {$key} has no successful independent verification reference.");
            }
            if ($completion !== null && $verification !== null && ! $this->after($verification['occurred_at'] ?? null, $completion['occurred_at'] ?? null)) {
                $conflicts[] = $this->issue('invalid_closure_verification_sequence', "Closure Eligibility Review {$key} has verification that does not follow the completion claim.");
            }
            foreach (['closure_authority_basis', 'reviewed_by', 'reviewed_at'] as $field) {
                if (empty($review[$field])) {
                    $eligibilityGaps[] = $this->issue('incomplete_closure_eligibility_review', "Closure Eligibility Review {$key} lacks {$field}.");
                }
            }
            if (empty($review['closure_evidence_record_key'])) {
                $evidenceGaps[] = $this->issue('missing_closure_eligibility_evidence', "Closure Eligibility Review {$key} lacks closure review Evidence.");
            }

            $eligibilityReviews[] = [
                ...$review,
                'closure_eligible' => $gapsBefore === count($eligibilityGaps) + count($evidenceGaps)
                    && $conflictsBefore === count($conflicts),
                'closure_decision_issued' => false,
            ];
        }

        return new ResolvedControlReviewClosureEligibility(
            schemaVersion: $definition->schemaVersion,
            requirements: $definition->requirements,
            reviews: $definition->reviews,
            eligibilityReviews: $eligibilityReviews,
            conflicts: $conflicts,
            eligibilityGaps: $eligibilityGaps,
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
     * @param  list<array<string, mixed>>  $outcomes
     * @return array<string, list<array<string, mixed>>>
     */
    private function outcomesByAction(array $outcomes): array
    {
        $grouped = [];
        foreach ($outcomes as $outcome) {
            if (is_string($outcome['action_key'] ?? null)) {
                $grouped[$outcome['action_key']][] = $outcome;
            }
        }

        return $grouped;
    }

    /**
     * @param  list<array<string, mixed>>  $outcomes
     * @return array<string, mixed>|null
     */
    private function findOutcome(array $outcomes, string $type): ?array
    {
        foreach ($outcomes as $outcome) {
            if (($outcome['outcome_type'] ?? null) === $type) {
                return $outcome;
            }
        }

        return null;
    }

    private function after(mixed $later, mixed $earlier): bool
    {
        if (! is_string($later) || ! is_string($earlier)) {
            return false;
        }
        try {
            return new DateTimeImmutable($later) > new DateTimeImmutable($earlier);
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
