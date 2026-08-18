<?php

namespace App\ControlHistory;

final readonly class ResolvedInstitutionalControlHistoryAnchorVerification
{
    /**
     * @param  list<array<string, mixed>>  $eventComparisons
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $verificationGaps
     */
    public function __construct(
        public int $schemaVersion,
        public string $verificationKey,
        public string $source,
        public string $algorithm,
        public ?string $suppliedHistoryAnchor,
        public string $resolvedHistoryAnchor,
        public array $eventComparisons,
        public array $conflicts,
        public array $verificationGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $status = match (true) {
            $this->conflicts !== [] => 'conflict_detected',
            $this->suppliedHistoryAnchor === null => 'not_supplied',
            $this->verificationGaps !== [] => 'mismatch_detected',
            default => 'verified',
        };

        return [
            'schema_version' => $this->schemaVersion,
            'verification_key' => $this->verificationKey,
            'source' => $this->source,
            'algorithm' => $this->algorithm,
            'status' => $status,
            'supplied_history_anchor' => $this->suppliedHistoryAnchor,
            'resolved_history_anchor' => $this->resolvedHistoryAnchor,
            'event_comparisons' => $this->eventComparisons,
            'counts' => [
                'event_comparisons' => count($this->eventComparisons),
                'conflicts' => count($this->conflicts),
                'verification_gaps' => count($this->verificationGaps),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'verification_gaps' => $this->verificationGaps,
            ],
            'principles' => [
                'Verification compares supplied anchors with resolved chronology; it does not create history.',
                'A missing supplied anchor is unresolved, not verified by absence.',
                'Anchor mismatch is an integrity finding, not an authority or workflow decision.',
                'Payloads and secrets remain outside the verification projection.',
            ],
        ];
    }
}
