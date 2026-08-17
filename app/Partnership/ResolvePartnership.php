<?php

namespace App\Partnership;

final class ResolvePartnership
{
    /**
     * Resolve canonical formation facts and constitutional rules without inventing missing decisions.
     */
    public function handle(PartnershipDefinition $definition): ResolvedPartnership
    {
        /** @var list<array<string, mixed>> $partners */
        $partners = $definition->formation['founding_partners'];
        /** @var array<string, mixed> $economics */
        $economics = $definition->formation['economics'];
        /** @var list<array<string, mixed>> $offices */
        $offices = $definition->constitution['offices'];
        /** @var list<array<string, mixed>> $responsibilities */
        $responsibilities = $definition->constitution['responsibility_assignments'];

        $partnerNames = [];

        foreach ($partners as $partner) {
            $partnerNames[$partner['key']] = $partner['name'];
        }

        $conflicts = $this->detectConflicts($partners, $offices, $partnerNames);
        $consistencyChecks = $this->buildConsistencyChecks($partners, $economics, $offices, $conflicts);
        $decisionGaps = [];
        $counselReview = [];

        foreach ($definition->decisions as $decision) {
            if (in_array($decision->institutionalState, [ResolutionStatus::Unresolved, ResolutionStatus::NotYetReady], true)) {
                $decisionGaps[] = $decision->toArray();
            }

            if ($decision->legalState === ResolutionStatus::CounselReview) {
                $counselReview[] = $decision->toArray();
            }
        }

        $responsibilityProjection = [];
        $responsibilityGaps = [];

        foreach ($responsibilities as $responsibility) {
            $holderNames = array_map(
                static fn (string $holder): string => $partnerNames[$holder],
                $responsibility['holders'],
            );

            $responsibilityProjection[] = [
                ...$responsibility,
                'holder_names' => $holderNames,
                'status' => $holderNames === [] ? 'unassigned' : 'assigned',
            ];

            if ($holderNames === []) {
                $responsibilityGaps[] = [
                    'key' => $responsibility['key'],
                    'label' => $responsibility['label'],
                    'type' => 'unassigned_responsibility',
                ];
            }
        }

        $managementProjection = [];

        foreach ($offices as $office) {
            $holder = $office['holder'];
            $managementProjection[] = [
                ...$office,
                'holder_name' => is_string($holder) && isset($partnerNames[$holder])
                    ? $partnerNames[$holder]
                    : null,
            ];

            if ($office['required'] === true && $office['holder'] === null) {
                $responsibilityGaps[] = [
                    'key' => $office['key'],
                    'label' => $office['name'],
                    'type' => 'required_office_vacant',
                ];
            }
        }

        return new ResolvedPartnership(
            schemaVersion: $definition->schemaVersion,
            formation: $definition->formation,
            constitution: $definition->constitution,
            decisions: $definition->decisions,
            projections: [
                'partnership' => $partners,
                'management' => $managementProjection,
                'responsibilities' => $responsibilityProjection,
                'economics' => [
                    'basis' => $economics['basis'],
                    'basis_definition' => $economics['basis_definition'],
                    'partner_allocations' => array_map(
                        static fn (array $partner): array => [
                            'key' => $partner['key'],
                            'name' => $partner['name'],
                            'percentage' => $partner['economic_allocation'],
                        ],
                        $partners,
                    ),
                    'firm_allocation' => $economics['firm_allocation'],
                ],
            ],
            consistencyChecks: $consistencyChecks,
            conflicts: $conflicts,
            decisionGaps: $decisionGaps,
            counselReview: $counselReview,
            responsibilityGaps: $responsibilityGaps,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $partners
     * @param  list<array<string, mixed>>  $offices
     * @param  array<string, string>  $partnerNames
     * @return list<array{code: string, message: string}>
     */
    private function detectConflicts(array $partners, array $offices, array $partnerNames): array
    {
        $conflicts = [];
        $partnerKeys = array_column($partners, 'key');

        if (count($partnerKeys) !== count(array_unique($partnerKeys))) {
            $conflicts[] = [
                'code' => 'duplicate_partner_key',
                'message' => 'Every Partner must have a unique institutional key.',
            ];
        }

        foreach ($offices as $office) {
            if ($office['holder'] !== null && ! isset($partnerNames[$office['holder']])) {
                $conflicts[] = [
                    'code' => 'unknown_office_holder',
                    'message' => "The {$office['name']} office refers to an unknown holder.",
                ];
            }
        }

        return $conflicts;
    }

    /**
     * @param  list<array<string, mixed>>  $partners
     * @param  array<string, mixed>  $economics
     * @param  list<array<string, mixed>>  $offices
     * @param  list<array{code: string, message: string}>  $conflicts
     * @return list<array{code: string, status: string, message: string}>
     */
    private function buildConsistencyChecks(array $partners, array $economics, array $offices, array $conflicts): array
    {
        $governanceTotal = array_sum(array_column($partners, 'governance_weight'));
        $partnerEconomicTotal = array_sum(array_column($partners, 'economic_allocation'));
        $firmAllocation = $economics['firm_allocation'];
        $economicTotal = $partnerEconomicTotal + $firmAllocation['percentage'];
        $managingPartnerOffices = array_filter(
            $offices,
            static fn (array $office): bool => $office['key'] === 'managing-partner',
        );

        return [
            [
                'code' => 'governance_total',
                'status' => $governanceTotal === 100 ? 'passed' : 'failed',
                'message' => "Founding governance totals {$governanceTotal}%.",
            ],
            [
                'code' => 'economic_total',
                'status' => $economicTotal === 100 ? 'passed' : 'failed',
                'message' => "Engagement Contribution allocations total {$economicTotal}%.",
            ],
            [
                'code' => 'firm_allocation_not_partner',
                'status' => $firmAllocation['recipient_type'] === 'firm' ? 'passed' : 'failed',
                'message' => 'The Firm Allocation is represented as an institutional recipient, not a Partner.',
            ],
            [
                'code' => 'managing_partner_office',
                'status' => count($managingPartnerOffices) === 1 ? 'passed' : 'failed',
                'message' => 'Exactly one Managing Partner office is defined.',
            ],
            [
                'code' => 'institutional_conflicts',
                'status' => $conflicts === [] ? 'passed' : 'failed',
                'message' => $conflicts === []
                    ? 'No structural conflicts were detected.'
                    : 'Structural conflicts require resolution.',
            ],
        ];
    }
}
