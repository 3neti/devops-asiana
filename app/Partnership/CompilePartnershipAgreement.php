<?php

namespace App\Partnership;

use DateTimeImmutable;
use JsonException;

final class CompilePartnershipAgreement
{
    public function handle(
        PartnershipDefinition $definition,
        ResolvedPartnership $partnership,
        ?DateTimeImmutable $compiledAt = null,
    ): PartnershipAgreementCompilation {
        $decisionGaps = $partnership->decisionGaps;
        $counselReview = $partnership->counselReview;

        foreach ($this->formationGaps($partnership->formation) as $gap) {
            $decisionGaps[] = $gap;
        }
        foreach ($this->formationCounselReview($partnership->formation) as $review) {
            $counselReview[] = $review;
        }

        $resolvedProvisions = $this->resolvedProvisions($partnership, $decisionGaps);
        $sourceFingerprint = $this->fingerprint([
            'schema_version' => $definition->schemaVersion,
            'formation' => $definition->formation,
            'constitution' => $definition->constitution,
            'decisions' => array_map(
                static fn (InstitutionalDecision $decision): array => $decision->toArray(),
                $definition->decisions,
            ),
        ]);
        $markdown = $this->renderMarkdown($partnership, $decisionGaps, $counselReview);
        $agreementFingerprint = hash('sha256', $markdown);
        $compilationId = hash('sha256', $sourceFingerprint.'|'.$agreementFingerprint);

        return new PartnershipAgreementCompilation(
            schemaVersion: $definition->schemaVersion,
            status: $partnership->conflicts === [] ? 'working_draft' : 'conflict_detected',
            sourceFingerprint: $sourceFingerprint,
            agreementFingerprint: $agreementFingerprint,
            compilationId: $compilationId,
            sourceCommit: getenv('GIT_COMMIT') ?: null,
            compiledAt: ($compiledAt ?? new DateTimeImmutable)->format(DateTimeImmutable::ATOM),
            resolvedProvisions: $resolvedProvisions,
            decisionGaps: $decisionGaps,
            counselReview: $counselReview,
            conflicts: $partnership->conflicts,
            markdown: $markdown,
        );
    }

    /**
     * @param  array<string, mixed>  $formation
     * @return list<array<string, mixed>>
     */
    private function formationGaps(array $formation): array
    {
        $firm = $formation['firm'];
        $gaps = [];
        $missing = [
            'principal-office' => ['Principal Office', 'The principal office has not been determined.'],
            'formation-commencement' => ['Formation Commencement', 'The effective or commencement basis has not been determined.'],
            'partnership-purpose' => ['Partnership Purpose', 'The Partnership purpose has not been stated in the canonical formation source.'],
            'partnership-term' => ['Partnership Term', 'The Partnership term has not been stated in the canonical formation source.'],
            'formal-loss-sharing' => ['Formal Loss-Sharing Treatment', 'Formal loss-sharing treatment remains unresolved.'],
        ];

        foreach ($missing as $key => [$label, $statement]) {
            $value = match ($key) {
                'principal-office' => $firm['principal_office'] ?? null,
                'formation-commencement' => $firm['effective_date'] ?? null,
                'partnership-purpose' => $formation['purpose']['statement'] ?? null,
                default => $formation[$key === 'partnership-term' ? 'term' : 'loss_sharing'] ?? null,
            };
            if ($value === null || $value === '') {
                $gaps[] = [
                    'key' => $key,
                    'label' => $label,
                    'institutional_state' => ResolutionStatus::Unresolved->value,
                    'institutional_state_label' => ResolutionStatus::Unresolved->label(),
                    'legal_state' => ResolutionStatus::NotYetReady->value,
                    'legal_state_label' => ResolutionStatus::NotYetReady->label(),
                    'statement' => $statement,
                ];
            }
        }

        return $gaps;
    }

    /**
     * @param  array<string, mixed>  $formation
     * @return list<array<string, mixed>>
     */
    private function formationCounselReview(array $formation): array
    {
        $reviews = [];
        if (($formation['firm']['legal_status'] ?? null) !== null) {
            $reviews[] = [
                'key' => 'legal-form-and-validity',
                'label' => 'Legal form and validity',
                'institutional_state' => ResolutionStatus::Resolved->value,
                'institutional_state_label' => ResolutionStatus::Resolved->label(),
                'legal_state' => ResolutionStatus::CounselReview->value,
                'legal_state_label' => ResolutionStatus::CounselReview->label(),
                'statement' => 'The working legal characterization remains subject to Philippine counsel review.',
            ];
        }
        $reviews[] = [
            'key' => 'firm-allocation-treatment',
            'label' => 'Firm Allocation legal and accounting treatment',
            'institutional_state' => ResolutionStatus::Resolved->value,
            'institutional_state_label' => ResolutionStatus::Resolved->label(),
            'legal_state' => ResolutionStatus::CounselReview->value,
            'legal_state_label' => ResolutionStatus::CounselReview->label(),
            'statement' => 'The Firm Allocation is institutional intent; formal legal, accounting, and tax treatment requires review.',
        ];

        return $reviews;
    }

    /**
     * @param  list<array<string, mixed>>  $decisionGaps
     * @return list<array<string, mixed>>
     */
    private function resolvedProvisions(ResolvedPartnership $partnership, array $decisionGaps): array
    {
        $formation = $partnership->formation;
        $constitution = $partnership->constitution;
        $resolved = [];
        $facts = [
            ['key' => 'firm-identity', 'label' => 'Firm identity', 'ready' => ! empty($formation['firm']['name']) && ! empty($formation['firm']['jurisdiction']) && ! empty($formation['firm']['legal_form'])],
            ['key' => 'founding-partners', 'label' => 'Founding Partners', 'ready' => $partnership->projections['partnership'] !== []],
            ['key' => 'governance-weights', 'label' => 'Founding governance weights', 'ready' => $this->checkPassed($partnership, 'governance_total')],
            ['key' => 'management-office', 'label' => 'Managing Partner office', 'ready' => $this->checkPassed($partnership, 'managing_partner_office')],
            ['key' => 'engagement-economics', 'label' => 'Engagement Contribution economics', 'ready' => $this->checkPassed($partnership, 'economic_total')],
            ['key' => 'firm-allocation', 'label' => 'Firm Allocation as institutional recipient', 'ready' => $this->checkPassed($partnership, 'firm_allocation_not_partner')],
            ['key' => 'reserved-matters', 'label' => 'Reserved Matters', 'ready' => $constitution['reserved_matters'] !== []],
            ['key' => 'authority-principles', 'label' => 'Authority principles', 'ready' => $constitution['authority_principles'] !== []],
            ['key' => 'responsibility-assignments', 'label' => 'Responsibility assignments', 'ready' => $constitution['responsibility_assignments'] !== []],
        ];
        foreach ($facts as $fact) {
            if ($fact['ready']) {
                $resolved[] = ['key' => $fact['key'], 'label' => $fact['label'], 'source' => 'Partnership Formation and Constitution'];
            }
        }
        foreach ($partnership->decisions as $decision) {
            if ($decision->institutionalState === ResolutionStatus::Resolved && ! in_array($decision->key, array_column($decisionGaps, 'key'), true)) {
                $resolved[] = ['key' => $decision->key, 'label' => $decision->label, 'source' => 'Institutional Decision'];
            }
        }

        return $resolved;
    }

    private function checkPassed(ResolvedPartnership $partnership, string $code): bool
    {
        foreach ($partnership->consistencyChecks as $check) {
            if ($check['code'] === $code) {
                return $check['status'] === 'passed';
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $decisionGaps
     * @param  list<array<string, mixed>>  $counselReview
     */
    private function renderMarkdown(ResolvedPartnership $partnership, array $decisionGaps, array $counselReview): string
    {
        $firm = $partnership->formation['firm'];
        $purpose = $partnership->formation['purpose'] ?? null;
        $partners = $partnership->projections['partnership'];
        $economics = $partnership->projections['economics'];
        $lines = [
            '# Partnership Agreement',
            '',
            '> WORKING DRAFT — projection of currently accepted institutional sources.',
            '',
            'This document is a deterministic working projection of DevOps Asiana institutional intent. It is not legal advice and is not a representation that the Partnership Agreement is legally valid, executed, or effective.',
            '',
            '## I. Firm and Formation',
            '',
            "- **Firm:** {$firm['name']}",
            "- **Legal form:** {$firm['legal_form']}",
            "- **Jurisdiction:** {$firm['jurisdiction']}",
            '- **Principal Office:** '.$this->valueOrUnresolved($firm['principal_office'] ?? null, 'The principal office has not been determined.'),
            '- **Commencement:** '.$this->valueOrUnresolved($firm['effective_date'] ?? null, 'Formation commencement has not been determined.'),
            '',
            '## II. Institutional Purpose',
            '',
            $purpose['statement'] ?? '[UNRESOLVED] The Partnership purpose has not been determined.',
            '',
            '> '.$this->valueOrUnresolved($purpose['professional_doctrine'] ?? null, 'The professional accountability doctrine has not been determined.'),
            '',
            '**Initial market specialization:**',
        ];
        foreach ($purpose['initial_market_specialization'] ?? [] as $market) {
            $lines[] = "- {$market}";
        }
        $lines[] = '';
        if (isset($purpose['scope_boundary'])) {
            $lines[] = $purpose['scope_boundary'];
            $lines[] = '';
        }
        $lines[] = '**Incidental institutional capacity:**';
        foreach ($purpose['incidental_capacity'] ?? [] as $capacity) {
            $lines[] = "- {$capacity}";
        }
        $lines[] = '';
        $lines[] = 'Institutional intent is resolved; formal legal wording and enforceability remain subject to Philippine counsel review.';
        $lines[] = '';
        $lines[] = '## III. Founding Partners and Governance';
        foreach ($partners as $partner) {
            $lines[] = "### {$partner['name']}";
            $lines[] = "- Partner status: {$partner['partner_status']}";
            $lines[] = "- Governance weight: {$partner['governance_weight']}%";
            $lines[] = '- Operational posture: '.$partner['operational_posture'];
            $lines[] = '';
        }
        $lines[] = 'Equal governance weight does not resolve the currently open deadlock mechanism.';
        $lines[] = '';
        $lines[] = '## IV. Management and Authority';
        $lines[] = '';
        foreach ($partnership->projections['management'] as $office) {
            $lines[] = "- **{$office['name']}:** ".($office['holder_name'] ?? '[UNRESOLVED — office is vacant]').'. Authority derives from the Partnership Agreement and Authority Matrix, subject to Reserved Matters.';
        }
        $lines[] = '';
        $lines[] = 'Firm Authority, Client Mandate, Specific Approval, and Technical Access remain separate gates.';
        $lines[] = '';
        $lines[] = '## V. Economics';
        $lines[] = '';
        $lines[] = "The conceptual allocation base is **{$economics['basis']}**: {$economics['basis_definition']}";
        foreach ($economics['partner_allocations'] as $allocation) {
            $lines[] = "- {$allocation['name']}: {$allocation['percentage']}%";
        }
        $lines[] = "- {$economics['firm_allocation']['label']}: {$economics['firm_allocation']['percentage']}% (institutional recipient, not a Partner)";
        $lines[] = '';
        $lines[] = '## VI. Reserved Matters';
        $lines[] = '';
        foreach ($partnership->constitution['reserved_matters'] as $matter) {
            $lines[] = "- {$matter}";
        }
        $lines[] = '';
        $lines[] = '## VII. Decisions Required';
        $lines[] = '';
        foreach ($decisionGaps as $gap) {
            $lines[] = "### [UNRESOLVED] {$gap['label']}";
            $lines[] = $gap['statement'];
            $lines[] = '';
        }
        if ($decisionGaps === []) {
            $lines[] = 'No unresolved decisions are currently reported.';
            $lines[] = '';
        }
        $lines[] = '## VIII. Counsel Review';
        $lines[] = '';
        foreach ($counselReview as $review) {
            $lines[] = "### [COUNSEL REVIEW] {$review['label']}";
            $lines[] = $review['statement'];
            $lines[] = '';
        }
        $lines[] = '## IX. Institutional Disclaimer';
        $lines[] = '';
        $lines[] = 'AI may draft changes to canonical sources, but humans constitute the Partnership. Compilation does not advance legal or institutional status.';

        return implode("\n", $lines)."\n";
    }

    private function valueOrUnresolved(mixed $value, string $message): string
    {
        return $value === null || $value === '' ? "[UNRESOLVED] {$message}" : (string) $value;
    }

    /** @param array<string, mixed> $value */
    private function fingerprint(array $value): string
    {
        try {
            return hash('sha256', json_encode($this->canonicalize($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } catch (JsonException $exception) {
            throw new \RuntimeException('Unable to serialize Partnership Agreement compilation input.', previous: $exception);
        }
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }
        ksort($value);

        return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
    }
}
