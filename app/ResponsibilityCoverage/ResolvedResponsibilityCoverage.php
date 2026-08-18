<?php

namespace App\ResponsibilityCoverage;

final readonly class ResolvedResponsibilityCoverage
{
    /**
     * @param  list<array<string, mixed>>  $requirements
     * @param  list<array<string, mixed>>  $separationConstraints
     * @param  array<string, int>  $coverageCounts
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, requirement_key: string, message: string}>  $vacancies
     * @param  list<array{code: string, requirement_key: string, message: string}>  $qualificationGaps
     * @param  list<array{code: string, requirement_key: string, message: string}>  $successionGaps
     * @param  list<array{code: string, holder_key: string, holder_name: string, requirement_keys: list<string>, message: string}>  $concentrationExposures
     * @param  list<array{code: string, constraint_key: string, holder_keys: list<string>, message: string}>  $separationConflicts
     * @param  list<array{code: string, requirement_key: string, message: string}>  $pendingRequirements
     */
    public function __construct(
        public int $schemaVersion,
        public int $concentrationReviewThreshold,
        public array $requirements,
        public array $separationConstraints,
        public array $coverageCounts,
        public array $conflicts,
        public array $vacancies,
        public array $qualificationGaps,
        public array $successionGaps,
        public array $concentrationExposures,
        public array $separationConflicts,
        public array $pendingRequirements,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [], $this->separationConflicts !== [] => 'conflict_detected',
                $this->vacancies !== [], $this->qualificationGaps !== [], $this->successionGaps !== [], $this->concentrationExposures !== [], $this->pendingRequirements !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'concentration_review_threshold' => $this->concentrationReviewThreshold,
            'requirements' => $this->requirements,
            'separation_constraints' => $this->separationConstraints,
            'counts' => [
                'requirements' => count($this->requirements),
                'covered' => $this->coverageCounts['covered'],
                'vacant' => $this->coverageCounts['vacant'],
                'pending_activation' => $this->coverageCounts['pending_activation'],
                'conflicted' => $this->coverageCounts['conflicted'],
                'succession_gaps' => count($this->successionGaps),
                'concentration_exposures' => count($this->concentrationExposures),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'vacancies' => $this->vacancies,
                'qualification_gaps' => $this->qualificationGaps,
                'succession_gaps' => $this->successionGaps,
                'concentration_exposures' => $this->concentrationExposures,
                'separation_conflicts' => $this->separationConflicts,
                'pending_requirements' => $this->pendingRequirements,
            ],
            'principles' => [
                'People hold offices or responsibilities; the office or constitutional status remains the source of authority.',
                'A Draft policy may identify a future requirement but cannot create operative authority.',
                'Vacancy, qualification, separation, concentration, and succession are distinct institutional conditions.',
                'Concentration exposure triggers review; it does not silently revoke otherwise valid authority.',
                'No title, ownership interest, technical access, or past practice fills a responsibility gap.',
            ],
        ];
    }
}
