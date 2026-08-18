<?php

namespace App\RetentionFindings;

use App\CorrectiveActions\ResolvedCorrectiveActions;
use App\RetentionReviews\ResolvedRetentionReviews;

final class ResolveRetentionFindingLinks
{
    public function handle(
        RetentionFindingLinkDefinition $definition,
        ResolvedRetentionReviews $reviews,
        ResolvedCorrectiveActions $actions,
    ): ResolvedRetentionFindingLinks {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $findingGaps */
        $findingGaps = [];
        /** @var list<array{code: string, message: string}> $actionGaps */
        $actionGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        /** @var list<string> $linkKeys */
        $linkKeys = [];
        /** @var list<array<string, mixed>> $resolved */
        $resolved = [];
        $reviewRecords = $this->indexByKey($reviews->reviews);
        $actionRecords = $this->indexByKey($actions->correctiveActions);
        $reviewEvidence = $this->reviewEvidence($reviews->reviews);

        foreach ($definition->links as $link) {
            $key = (string) ($link['key'] ?? '');
            $issuesBefore = count($conflicts) + count($findingGaps) + count($actionGaps) + count($evidenceGaps);
            if ($key === '' || in_array($key, $linkKeys, true)) {
                $conflicts[] = $this->issue('invalid_retention_finding_link_key', 'Retention Finding Link has a missing or duplicate key.');
            }
            $linkKeys[] = $key;

            $reviewKey = (string) ($link['retention_review_key'] ?? '');
            $review = $reviewRecords[$reviewKey] ?? null;
            if (! is_array($review)) {
                $findingGaps[] = $this->issue('retention_review_not_found', "Retention Finding Link {$key} references an unknown Retention Review.");
            } elseif (($review['review_resolved'] ?? false) !== true) {
                $findingGaps[] = $this->issue('retention_review_not_resolved', "Retention Review {$reviewKey} has unresolved review or exception gaps.");
            } elseif (! in_array($review['outcome'] ?? null, ['exception_required', 'disposition_due'], true)) {
                $findingGaps[] = $this->issue('retention_review_is_not_a_finding', "Retention Review {$reviewKey} does not contain a remediation finding.");
            }

            $actionKey = (string) ($link['corrective_action_key'] ?? '');
            if (! isset($actionRecords[$actionKey])) {
                $actionGaps[] = $this->issue('corrective_action_not_found', "Retention Finding Link {$key} references an unknown Corrective Action.");
            }

            if (empty($link['linked_by']) || empty($link['linked_at']) || empty($link['reason'])) {
                $findingGaps[] = $this->issue('incomplete_retention_finding_link', "Retention Finding Link {$key} lacks linker, time, or reason.");
            }

            $evidenceKey = (string) ($link['evidence_record_key'] ?? '');
            if (! isset($reviewEvidence[$reviewKey][$evidenceKey])) {
                $evidenceGaps[] = $this->issue('retention_finding_link_evidence_not_reviewed', "Retention Finding Link {$key} references Evidence not attached to its Retention Review.");
            }

            if ($issuesBefore === count($conflicts) + count($findingGaps) + count($actionGaps) + count($evidenceGaps)) {
                $resolved[] = [...$link, 'link_resolved' => true];
            }
        }

        return new ResolvedRetentionFindingLinks(
            schemaVersion: $definition->schemaVersion,
            requirements: $definition->requirements,
            links: $definition->links,
            resolvedLinks: $resolved,
            conflicts: $conflicts,
            findingGaps: $findingGaps,
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

    /**
     * @param  list<array<string, mixed>>  $reviews
     * @return array<string, array<string, true>>
     */
    private function reviewEvidence(array $reviews): array
    {
        $evidence = [];
        foreach ($reviews as $review) {
            if (is_string($review['key'] ?? null) && is_string($review['evidence_record_key'] ?? null)) {
                $evidence[$review['key']][$review['evidence_record_key']] = true;
            }
        }

        return $evidence;
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
