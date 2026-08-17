<?php

namespace App\Partnership;

final readonly class InstitutionalDecision
{
    /**
     * @param  array{key: string, label: string, institutional_state: string, legal_state: string, statement: string}  $decision
     */
    public static function fromArray(array $decision): self
    {
        return new self(
            key: $decision['key'],
            label: $decision['label'],
            institutionalState: ResolutionStatus::from($decision['institutional_state']),
            legalState: ResolutionStatus::from($decision['legal_state']),
            statement: $decision['statement'],
        );
    }

    public function __construct(
        public string $key,
        public string $label,
        public ResolutionStatus $institutionalState,
        public ResolutionStatus $legalState,
        public string $statement,
    ) {}

    /**
     * @return array{key: string, label: string, institutional_state: string, institutional_state_label: string, legal_state: string, legal_state_label: string, statement: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'institutional_state' => $this->institutionalState->value,
            'institutional_state_label' => $this->institutionalState->label(),
            'legal_state' => $this->legalState->value,
            'legal_state_label' => $this->legalState->label(),
            'statement' => $this->statement,
        ];
    }
}
