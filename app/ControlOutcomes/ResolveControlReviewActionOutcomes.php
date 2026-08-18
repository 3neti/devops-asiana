<?php

namespace App\ControlOutcomes;

use App\ControlActions\ResolvedControlReviewActions;

final class ResolveControlReviewActionOutcomes
{
    public function handle(
        ControlReviewActionOutcomeDefinition $definition,
        ResolvedControlReviewActions $actions,
    ): ResolvedControlReviewActionOutcomes {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $outcomeGaps */
        $outcomeGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        /** @var list<string> $keys */
        $keys = [];
        /** @var list<array<string, mixed>> $resolved */
        $resolved = [];
        $actionRecords = $this->indexByKey($actions->resolvedActions);

        foreach ($definition->outcomes as $outcome) {
            $key = (string) ($outcome['key'] ?? '');
            $issuesBefore = count($conflicts) + count($outcomeGaps) + count($evidenceGaps);
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_control_review_outcome_key', 'Control Review Action Outcome has a missing or duplicate key.');
            }
            $keys[] = $key;

            $actionKey = (string) ($outcome['action_key'] ?? '');
            $action = $actionRecords[$actionKey] ?? null;
            if (! is_array($action)) {
                $conflicts[] = $this->issue('outcome_action_not_admitted', "Control Review Action Outcome {$key} does not reference an admitted Action.");
            }

            $type = (string) ($outcome['outcome_type'] ?? '');
            if (! in_array($type, ['progress', 'blocked', 'completion_claim', 'verification_reference'], true)) {
                $conflicts[] = $this->issue('invalid_control_review_outcome_type', "Control Review Action Outcome {$key} has an invalid outcome type.");
            }
            foreach (['actor', 'occurred_at', 'summary'] as $field) {
                if (empty($outcome[$field])) {
                    $outcomeGaps[] = $this->issue('incomplete_control_review_outcome', "Control Review Action Outcome {$key} lacks {$field}.");
                }
            }
            if ($type === 'verification_reference') {
                if (empty($outcome['verified_by']) || empty($outcome['verification_outcome'])) {
                    $outcomeGaps[] = $this->issue('incomplete_verification_reference', "Control Review Action Outcome {$key} lacks verifier or verification outcome.");
                }
                if (is_array($action) && ($outcome['verified_by'] ?? null) === ($action['owner'] ?? null)) {
                    $conflicts[] = $this->issue('self_verified_control_review_action', "Control Review Action Outcome {$key} is verified by the action owner.");
                }
            }
            if (empty($outcome['evidence_record_key'])) {
                $evidenceGaps[] = $this->issue('missing_control_review_outcome_evidence', "Control Review Action Outcome {$key} lacks its Evidence record reference.");
            }

            if ($issuesBefore === count($conflicts) + count($outcomeGaps) + count($evidenceGaps)) {
                $resolved[] = [...$outcome, 'outcome_resolved' => true, 'completion_or_closure_inferred' => false];
            }
        }

        return new ResolvedControlReviewActionOutcomes(
            schemaVersion: $definition->schemaVersion,
            requirements: $definition->requirements,
            outcomes: $definition->outcomes,
            resolvedOutcomes: $resolved,
            conflicts: $conflicts,
            outcomeGaps: $outcomeGaps,
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
