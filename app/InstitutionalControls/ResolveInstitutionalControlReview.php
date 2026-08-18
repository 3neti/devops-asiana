<?php

namespace App\InstitutionalControls;

use App\CorrectiveActions\ResolvedCorrectiveActions;
use App\EvidenceCustody\ResolvedEvidenceCustody;
use App\RetentionFindings\ResolvedRetentionFindingLinks;
use App\RetentionReviews\ResolvedRetentionReviews;

final class ResolveInstitutionalControlReview
{
    public function handle(
        InstitutionalControlReviewDefinition $definition,
        ResolvedEvidenceCustody $custody,
        ResolvedRetentionReviews $retentionReviews,
        ResolvedRetentionFindingLinks $retentionFindings,
        ResolvedCorrectiveActions $correctiveActions,
    ): ResolvedInstitutionalControlReview {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array<string, mixed>> $controlReviews */
        $controlReviews = [];
        /** @var list<string> $keys */
        $keys = [];
        $sources = $this->sources($custody, $retentionReviews, $retentionFindings, $correctiveActions);

        foreach ($definition->controls as $control) {
            $key = (string) ($control['key'] ?? '');
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_control_review_key', 'Institutional Control Review has a missing or duplicate control key.');
            }
            $keys[] = $key;

            $source = (string) ($control['source'] ?? '');
            if (! isset($sources[$source])) {
                $conflicts[] = $this->issue('unknown_control_review_source', "Institutional Control Review {$key} references an unknown source compiler.");
                $controlReviews[] = [
                    ...$control,
                    'status' => 'conflict_detected',
                    'gap_count' => 0,
                    'gaps' => [],
                ];

                continue;
            }

            $snapshot = $sources[$source];
            $gaps = $this->gaps($snapshot);
            $controlReviews[] = [
                ...$control,
                'status' => $gaps === [] ? 'consistent' : 'attention_required',
                'gap_count' => count($gaps),
                'gaps' => $gaps,
            ];
        }

        return new ResolvedInstitutionalControlReview(
            schemaVersion: $definition->schemaVersion,
            controls: $definition->controls,
            controlReviews: $controlReviews,
            conflicts: $conflicts,
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function sources(
        ResolvedEvidenceCustody $custody,
        ResolvedRetentionReviews $retentionReviews,
        ResolvedRetentionFindingLinks $retentionFindings,
        ResolvedCorrectiveActions $correctiveActions,
    ): array {
        return [
            'evidence-custody' => [
                'conflicts' => $custody->conflicts,
                'source_gaps' => $custody->sourceGaps,
                'custody_gaps' => $custody->custodyGaps,
                'retention_gaps' => $custody->retentionGaps,
                'integrity_gaps' => $custody->integrityGaps,
                'disposition_gaps' => $custody->dispositionGaps,
            ],
            'retention-reviews' => [
                'conflicts' => $retentionReviews->conflicts,
                'review_gaps' => $retentionReviews->reviewGaps,
                'exception_gaps' => $retentionReviews->exceptionGaps,
                'evidence_gaps' => $retentionReviews->evidenceGaps,
            ],
            'retention-findings' => [
                'conflicts' => $retentionFindings->conflicts,
                'finding_gaps' => $retentionFindings->findingGaps,
                'action_gaps' => $retentionFindings->actionGaps,
                'evidence_gaps' => $retentionFindings->evidenceGaps,
            ],
            'corrective-actions' => [
                'conflicts' => $correctiveActions->conflicts,
                'decision_gaps' => $correctiveActions->decisionGaps,
                'evidence_gaps' => $correctiveActions->evidenceGaps,
                'readiness_gaps' => $correctiveActions->readinessGaps,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return list<array{category: string, code: string, message: string}>
     */
    private function gaps(array $snapshot): array
    {
        $gaps = [];
        foreach ($snapshot as $category => $issues) {
            if (! is_array($issues)) {
                continue;
            }
            foreach ($issues as $issue) {
                if (is_array($issue) && isset($issue['code'], $issue['message'])) {
                    $gaps[] = [
                        'category' => $category,
                        'code' => $issue['code'],
                        'message' => $issue['message'],
                    ];
                }
            }
        }

        return $gaps;
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
