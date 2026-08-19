<?php

namespace App\Partnership;

final readonly class PartnershipAgreementCompilation
{
    /**
     * @param  list<array<string, mixed>>  $resolvedProvisions
     * @param  list<array<string, mixed>>  $decisionGaps
     * @param  list<array<string, mixed>>  $counselReview
     * @param  list<array{code: string, message: string}>  $conflicts
     */
    public function __construct(
        public int $schemaVersion,
        public string $status,
        public string $sourceFingerprint,
        public string $agreementFingerprint,
        public string $compilationId,
        public ?string $sourceCommit,
        public string $compiledAt,
        public array $resolvedProvisions,
        public array $decisionGaps,
        public array $counselReview,
        public array $conflicts,
        public string $markdown,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'status' => $this->status,
            'source_fingerprint' => $this->sourceFingerprint,
            'agreement_fingerprint' => $this->agreementFingerprint,
            'compilation_id' => $this->compilationId,
            'source_commit' => $this->sourceCommit,
            'compiled_at' => $this->compiledAt,
            'counts' => [
                'resolved_provisions' => count($this->resolvedProvisions),
                'decisions_required' => count($this->decisionGaps),
                'counsel_review' => count($this->counselReview),
                'conflicts' => count($this->conflicts),
            ],
            'resolved_provisions' => $this->resolvedProvisions,
            'decision_gaps' => $this->decisionGaps,
            'counsel_review' => $this->counselReview,
            'conflicts' => $this->conflicts,
            'agreement' => [
                'title' => 'Partnership Agreement',
                'status' => $this->status,
                'markdown' => $this->markdown,
            ],
            'principles' => [
                'The Partnership Agreement is a projection of resolved institutional sources, not canonical truth.',
                'Compilation is deterministic and does not call an AI model.',
                'Compilation does not approve, execute, or make the Agreement legally valid.',
                'Unresolved decisions, counsel review, and conflicts remain visible in the projection.',
            ],
            'disclaimer' => 'This working draft expresses institutional intent. It is not legal advice and is not a representation that a Partnership Agreement is legally valid, executed, or effective.',
        ];
    }
}
