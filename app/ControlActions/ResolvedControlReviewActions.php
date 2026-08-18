<?php

namespace App\ControlActions;

final readonly class ResolvedControlReviewActions
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $actions
     * @param  list<array<string, mixed>>  $resolvedActions
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $actionGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $actions,
        public array $resolvedActions,
        public array $conflicts,
        public array $actionGaps,
        public array $evidenceGaps,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $hasGaps = $this->actionGaps !== [] || $this->evidenceGaps !== [];

        return [
            'schema_version' => $this->schemaVersion,
            'compiler_status' => $this->conflicts !== []
                ? 'conflict_detected'
                : ($hasGaps ? 'consistent_with_gaps' : 'consistent'),
            'requirements' => $this->requirements,
            'actions' => $this->actions,
            'resolved_actions' => $this->resolvedActions,
            'counts' => [
                'actions' => count($this->actions),
                'resolved_actions' => count($this->resolvedActions),
            ],
            'reports' => [
                'conflicts' => $this->conflicts,
                'action_gaps' => $this->actionGaps,
                'evidence_gaps' => $this->evidenceGaps,
            ],
            'principles' => [
                'An action register records follow-up; it is not a generic task system.',
                'Action authorization is separate from Control Review sign-off.',
                'An action never creates, verifies, or closes a Corrective Action implicitly.',
                'Every admitted action has explicit owner, due date, authority basis, and Evidence.',
            ],
        ];
    }
}
