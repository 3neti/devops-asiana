<?php

namespace App\ControlActions;

use App\ControlSignoffs\ResolvedControlReviewSignoffs;

final class ResolveControlReviewActions
{
    public function handle(
        ControlReviewActionDefinition $definition,
        ResolvedControlReviewSignoffs $signoffs,
    ): ResolvedControlReviewActions {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $actionGaps */
        $actionGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        /** @var list<string> $keys */
        $keys = [];
        /** @var list<array<string, mixed>> $resolved */
        $resolved = [];
        $admittedSignoffs = $this->indexByKey($signoffs->resolvedSignoffs);

        foreach ($definition->actions as $action) {
            $key = (string) ($action['key'] ?? '');
            $issuesBefore = count($conflicts) + count($actionGaps) + count($evidenceGaps);
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_control_review_action_key', 'Control Review Action has a missing or duplicate key.');
            }
            $keys[] = $key;

            $signoffKey = (string) ($action['signoff_key'] ?? '');
            $signoff = $admittedSignoffs[$signoffKey] ?? null;
            if (! is_array($signoff)) {
                $conflicts[] = $this->issue('action_signoff_not_admitted', "Control Review Action {$key} does not reference an admitted Sign-off.");
            }

            $controlKey = (string) ($action['control_key'] ?? '');
            if (is_array($signoff) && ! in_array($controlKey, $signoff['reviewed_control_keys'] ?? [], true)) {
                $actionGaps[] = $this->issue('action_control_not_reviewed', "Control Review Action {$key} targets a control outside its Sign-off scope.");
            }

            if (! in_array($action['action_type'] ?? null, ['investigate', 'remediate', 'escalate', 'monitor'], true)) {
                $conflicts[] = $this->issue('invalid_control_review_action_type', "Control Review Action {$key} has an invalid action type.");
            }
            foreach (['owner', 'due_at', 'authority_basis', 'reason'] as $field) {
                if (empty($action[$field])) {
                    $actionGaps[] = $this->issue('incomplete_control_review_action', "Control Review Action {$key} lacks {$field}.");
                }
            }
            if (empty($action['evidence_record_key'])) {
                $evidenceGaps[] = $this->issue('missing_control_review_action_evidence', "Control Review Action {$key} lacks its Evidence record reference.");
            }

            if ($issuesBefore === count($conflicts) + count($actionGaps) + count($evidenceGaps)) {
                $resolved[] = [...$action, 'action_resolved' => true];
            }
        }

        return new ResolvedControlReviewActions(
            schemaVersion: $definition->schemaVersion,
            requirements: $definition->requirements,
            actions: $definition->actions,
            resolvedActions: $resolved,
            conflicts: $conflicts,
            actionGaps: $actionGaps,
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
