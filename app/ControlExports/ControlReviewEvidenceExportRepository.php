<?php

namespace App\ControlExports;

final class ControlReviewEvidenceExportRepository
{
    public function current(): ControlReviewEvidenceExportDefinition
    {
        $contents = file_get_contents(resource_path('institution/control-review-evidence-export.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Control Review Evidence Export definition.');
        }

        return ControlReviewEvidenceExportDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
