<?php

namespace App\FormationCompletion;

final readonly class ResolvedFormationCompletion
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  array<string, mixed>  $legalRequirementsRule
     * @param  array<string, mixed>  $capitalInitialization
     * @param  list<array<string, mixed>>  $commencementRecords
     * @param  list<array<string, mixed>>  $officeActivationBases
     * @param  list<array<string, mixed>>  $evidenceRecords
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $formationGaps
     * @param  list<array{code: string, message: string}>  $legalGaps
     * @param  list<array{code: string, message: string}>  $capitalGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @param  list<array{code: string, message: string}>  $counselReview
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $legalRequirementsRule,
        public array $capitalInitialization,
        public array $commencementRecords,
        public array $officeActivationBases,
        public array $evidenceRecords,
        public array $conflicts,
        public array $formationGaps,
        public array $legalGaps,
        public array $capitalGaps,
        public array $evidenceGaps,
        public array $counselReview,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => match (true) {
                $this->conflicts !== [] => 'conflict_detected',
                $this->formationGaps !== [], $this->legalGaps !== [], $this->capitalGaps !== [], $this->evidenceGaps !== [], $this->counselReview !== [] => 'consistent_with_gaps',
                default => 'consistent',
            },
            'firm_commenced' => $this->officeActivationBases !== [],
            'requirements' => $this->requirements,
            'legal_requirements_rule' => $this->legalRequirementsRule,
            'capital_initialization' => $this->capitalInitialization,
            'commencement_records' => $this->commencementRecords,
            'office_activation_bases' => $this->officeActivationBases,
            'evidence_records' => $this->evidenceRecords,
            'counts' => [
                'verified_commencements' => count(array_filter(
                    $this->commencementRecords,
                    static fn (array $record): bool => ($record['commencement_verified'] ?? false) === true,
                )),
                'office_activation_bases' => count($this->officeActivationBases),
                'evidence_records' => count($this->evidenceRecords),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'formation_gaps' => $this->formationGaps,
                'legal_gaps' => $this->legalGaps,
                'capital_gaps' => $this->capitalGaps,
                'evidence_gaps' => $this->evidenceGaps,
                'counsel_review' => $this->counselReview,
            ],
            'principles' => [
                'A drafted or executed document does not by itself prove that the Firm may commence.',
                'The applicable Philippine formation requirement set must be confirmed by counsel rather than encoded by assumption.',
                'Firm name, legal form, jurisdiction, principal office, effective date, and Founding Partners must match resolved Partnership truth.',
                'Capital initialization is separate from governance weight, economic allocation, and Firm Allocation.',
                'Application authentication, policy activation, and operational activity do not prove legal commencement.',
                'Formation-derived Offices and assignments require a verified commencement basis before activation.',
            ],
            'disclaimer' => 'This compiler records institutional readiness and counsel-confirmed evidence. It does not determine that a Philippine partnership legally exists.',
        ];
    }
}
