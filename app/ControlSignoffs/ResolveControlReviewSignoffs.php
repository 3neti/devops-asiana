<?php

namespace App\ControlSignoffs;

use App\ControlExports\ResolvedControlReviewEvidenceExport;

final class ResolveControlReviewSignoffs
{
    public function handle(
        ControlReviewSignoffDefinition $definition,
        ResolvedControlReviewEvidenceExport $export,
    ): ResolvedControlReviewSignoffs {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $reviewGaps */
        $reviewGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        /** @var list<string> $keys */
        $keys = [];
        /** @var list<array<string, mixed>> $resolved */
        $resolved = [];

        foreach ($definition->signoffs as $signoff) {
            $key = (string) ($signoff['key'] ?? '');
            $issuesBefore = count($conflicts) + count($reviewGaps) + count($evidenceGaps);
            if ($key === '' || in_array($key, $keys, true)) {
                $conflicts[] = $this->issue('invalid_control_review_signoff_key', 'Control Review Sign-off has a missing or duplicate key.');
            }
            $keys[] = $key;

            if (($signoff['export_key'] ?? null) !== $export->exportKey) {
                $conflicts[] = $this->issue('signoff_export_key_mismatch', "Control Review Sign-off {$key} does not reference the current export.");
            }
            if (($signoff['export_status'] ?? null) !== $export->status) {
                $conflicts[] = $this->issue('signoff_export_status_mismatch', "Control Review Sign-off {$key} does not snapshot the current export status.");
            }
            $outcome = (string) ($signoff['outcome'] ?? '');
            if (! in_array($outcome, ['reviewed', 'reviewed_with_findings', 'rejected'], true)) {
                $conflicts[] = $this->issue('invalid_control_review_signoff_outcome', "Control Review Sign-off {$key} has an invalid outcome.");
            }
            if ($export->status === 'attention_required' && $outcome === 'reviewed') {
                $conflicts[] = $this->issue('signoff_suppresses_findings', "Control Review Sign-off {$key} cannot report Reviewed while the export has attention findings.");
            }
            if (empty($signoff['reviewer']) || empty($signoff['reviewer_role']) || empty($signoff['reviewed_at']) || empty($signoff['basis'])) {
                $reviewGaps[] = $this->issue('incomplete_control_review_signoff', "Control Review Sign-off {$key} lacks reviewer, role, time, or basis.");
            }
            if (($signoff['acknowledges_no_approval'] ?? false) !== true) {
                $reviewGaps[] = $this->issue('signoff_approval_boundary_unacknowledged', "Control Review Sign-off {$key} must explicitly acknowledge that review is not approval or risk acceptance.");
            }
            if (empty($signoff['evidence_record_key'])) {
                $evidenceGaps[] = $this->issue('missing_control_review_signoff_evidence', "Control Review Sign-off {$key} lacks its Evidence record reference.");
            }

            if ($issuesBefore === count($conflicts) + count($reviewGaps) + count($evidenceGaps)) {
                $resolved[] = [...$signoff, 'signoff_resolved' => true];
            }
        }

        return new ResolvedControlReviewSignoffs(
            schemaVersion: $definition->schemaVersion,
            requirements: $definition->requirements,
            signoffs: $definition->signoffs,
            resolvedSignoffs: $resolved,
            conflicts: $conflicts,
            reviewGaps: $reviewGaps,
            evidenceGaps: $evidenceGaps,
        );
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
