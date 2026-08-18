<?php

namespace App\ControlExports;

use App\InstitutionalControls\ResolvedInstitutionalControlReview;

final class ResolveControlReviewEvidenceExport
{
    /** @var list<string> */
    private const ALLOWED_FIELDS = ['key', 'label', 'source', 'question', 'status', 'gap_count', 'gaps'];

    public function handle(
        ControlReviewEvidenceExportDefinition $definition,
        ResolvedInstitutionalControlReview $review,
    ): ResolvedControlReviewEvidenceExport {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        if ($definition->source !== 'institutional-control-review') {
            $conflicts[] = $this->issue('invalid_export_source', 'Control Review Evidence Export must source Institutional Control Review.');
        }
        if ($definition->includePayloads) {
            $conflicts[] = $this->issue('payload_export_forbidden', 'Control Review Evidence Export cannot include Evidence payloads or secrets.');
        }
        foreach ($definition->includedFields as $field) {
            if (! in_array($field, self::ALLOWED_FIELDS, true)) {
                $conflicts[] = $this->issue('invalid_export_field', "Control Review Evidence Export includes unsupported field {$field}.");
            }
        }

        /** @var list<array<string, mixed>> $controls */
        $controls = [];
        foreach ($review->controlReviews as $control) {
            $exported = [];
            foreach ($definition->includedFields as $field) {
                if ($field === 'gaps' && ! $definition->includeGapMessages) {
                    $exported[$field] = [];

                    continue;
                }
                $exported[$field] = $control[$field] ?? null;
            }
            $controls[] = $exported;
        }

        $status = match (true) {
            $conflicts !== [] || $review->conflicts !== [] => 'conflict_detected',
            $review->toArray()['compiler_status'] === 'attention_required' => 'attention_required',
            default => 'consistent',
        };

        return new ResolvedControlReviewEvidenceExport(
            schemaVersion: $definition->schemaVersion,
            exportKey: $definition->exportKey,
            source: $definition->source,
            payloadsExcluded: ! $definition->includePayloads,
            includedFields: $definition->includedFields,
            status: $status,
            controls: $controls,
            conflicts: [...$review->conflicts, ...$conflicts],
        );
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
