<?php

namespace App\ControlSignoffs;

final class ControlReviewSignoffRepository
{
    public function current(): ControlReviewSignoffDefinition
    {
        $contents = file_get_contents(resource_path('institution/control-review-signoffs.json'));
        if ($contents === false) {
            throw new \RuntimeException('Unable to read the canonical Control Review Sign-off definition.');
        }

        return ControlReviewSignoffDefinition::fromArray(json_decode($contents, true, flags: JSON_THROW_ON_ERROR));
    }
}
