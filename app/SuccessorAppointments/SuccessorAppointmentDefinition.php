<?php

namespace App\SuccessorAppointments;

final readonly class SuccessorAppointmentDefinition
{
    /**
     * @param  list<array<string, string>>  $requirements
     * @param  list<array<string, mixed>>  $appointmentRecords
     * @param  list<array<string, mixed>>  $evidenceRecords
     */
    public function __construct(
        public int $schemaVersion,
        public array $requirements,
        public array $appointmentRecords,
        public array $evidenceRecords,
    ) {}

    /** @param array<string, mixed> $definition */
    public static function fromArray(array $definition): self
    {
        return new self(
            schemaVersion: $definition['schema_version'],
            requirements: array_values($definition['requirements']),
            appointmentRecords: array_values($definition['appointment_records']),
            evidenceRecords: array_values($definition['evidence_records']),
        );
    }
}
