<?php

namespace App\FormationCompletion;

use App\Partnership\ResolvedPartnership;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveFormationCompletion
{
    public function handle(
        FormationCompletionDefinition $definition,
        ResolvedPartnership $partnership,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedFormationCompletion {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $formationGaps */
        $formationGaps = [];
        /** @var list<array{code: string, message: string}> $legalGaps */
        $legalGaps = [];
        /** @var list<array{code: string, message: string}> $capitalGaps */
        $capitalGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        /** @var list<array{code: string, message: string}> $counselReview */
        $counselReview = [];
        $resolvedAsOf = Carbon::instance($asOf ?? new DateTimeImmutable);
        $firm = $partnership->formation['firm'] ?? [];
        $founders = $this->indexByKey($partnership->formation['founding_partners'] ?? []);
        $firmEffectiveAt = $this->date($firm['effective_date'] ?? null);
        $evidence = $this->evidenceIndex($definition->evidenceRecords, $resolvedAsOf, $conflicts, $evidenceGaps);

        if (empty($firm['principal_office'])) {
            $formationGaps[] = $this->issue('principal_office_unresolved', 'The Firm principal office remains unresolved.');
        }
        if ($firmEffectiveAt === null) {
            $formationGaps[] = $this->issue('formation_effective_date_unresolved', 'The Firm effective date remains unresolved.');
        }
        if (($definition->legalRequirementsRule['jurisdiction'] ?? null) !== ($firm['jurisdiction'] ?? null)
            || ($definition->legalRequirementsRule['legal_form'] ?? null) !== ($firm['legal_form'] ?? null)) {
            $conflicts[] = $this->issue('formation_legal_context_mismatch', 'The legal requirement rule does not match the resolved Partnership jurisdiction and legal form.');
        }

        $requiredRecordTypes = $this->legalRequirementTypes($definition->legalRequirementsRule, $legalGaps, $counselReview);
        $capitalReferenceKeys = $this->capitalReferenceKeys(
            $definition->capitalInitialization,
            $founders,
            $evidence,
            $conflicts,
            $capitalGaps,
            $evidenceGaps,
            $counselReview,
        );

        if ($definition->commencementRecords === []) {
            $formationGaps[] = $this->issue('commencement_not_recorded', 'No Firm Commencement Record exists.');
        }
        if (count($definition->commencementRecords) > 1) {
            $conflicts[] = $this->issue('multiple_commencement_records', 'The Firm may have only one initial Commencement Record.');
        }

        $globalPrerequisitesResolved = $conflicts === []
            && $formationGaps === []
            && $legalGaps === []
            && $capitalGaps === []
            && $evidenceGaps === []
            && $counselReview === [];
        $resolvedRecords = [];
        $officeActivationBases = [];
        foreach ($definition->commencementRecords as $record) {
            $issueCount = $this->issueCount($conflicts, $formationGaps, $legalGaps, $capitalGaps, $evidenceGaps, $counselReview);
            $key = (string) ($record['key'] ?? '');
            if ($key === '' || ($record['status'] ?? null) !== 'confirmed') {
                $formationGaps[] = $this->issue('invalid_commencement_record', 'The Firm Commencement Record must have a key and Confirmed status.');
            }

            $snapshot = is_array($record['firm_snapshot'] ?? null) ? $record['firm_snapshot'] : [];
            foreach (['name', 'jurisdiction', 'legal_form', 'principal_office', 'effective_date'] as $field) {
                if (($snapshot[$field] ?? null) !== ($firm[$field] ?? null)) {
                    $conflicts[] = $this->issue('commencement_firm_snapshot_mismatch', "Firm Commencement {$key} contradicts resolved Partnership {$field}.");
                }
            }

            $founderKeys = array_values($record['founding_partner_identity_keys'] ?? []);
            if (! $this->sameValues($founderKeys, array_keys($founders))) {
                $conflicts[] = $this->issue('commencement_founder_mismatch', "Firm Commencement {$key} does not preserve every and only the Founding Partners.");
            }

            $instrument = is_array($record['constitutional_instrument'] ?? null) ? $record['constitutional_instrument'] : [];
            $instrumentExecutedAt = $this->date($instrument['executed_at'] ?? null);
            if (($instrument['type'] ?? null) !== 'partnership_agreement'
                || empty($instrument['repository_reference'])
                || empty($instrument['content_digest'])
                || ($instrument['counsel_confirmed'] ?? false) !== true
                || empty($instrument['counsel_confirmation_reference'])) {
                $formationGaps[] = $this->issue('incomplete_commencement_instrument', "Firm Commencement {$key} lacks a counsel-confirmed executed Partnership Agreement reference.");
            }
            if ($instrumentExecutedAt === null || ($firmEffectiveAt !== null && $instrumentExecutedAt->isAfter($firmEffectiveAt))) {
                $conflicts[] = $this->issue('invalid_commencement_instrument_time', "Firm Commencement {$key} has an invalid instrument chronology.");
            }
            if (! $this->hasEvidence($instrument['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_commencement_instrument_evidence', "Firm Commencement {$key} lacks instrument Evidence.");
            }

            $legalSnapshot = is_array($record['legal_requirements_snapshot'] ?? null) ? $record['legal_requirements_snapshot'] : [];
            if (($legalSnapshot['counsel_confirmation_reference'] ?? null) !== ($definition->legalRequirementsRule['counsel_confirmation_reference'] ?? null)
                || ! $this->sameValues(array_values($legalSnapshot['required_record_types'] ?? []), $requiredRecordTypes)) {
                $conflicts[] = $this->issue('commencement_legal_snapshot_mismatch', "Firm Commencement {$key} contradicts the counsel-confirmed legal requirement set.");
            }
            $completedRequirementTypes = [];
            $latestRequirementAt = null;
            foreach ($record['legal_requirement_records'] ?? [] as $requirementRecord) {
                $type = (string) ($requirementRecord['type'] ?? '');
                if (! in_array($type, $requiredRecordTypes, true) || in_array($type, $completedRequirementTypes, true) || empty($requirementRecord['reference'])) {
                    $conflicts[] = $this->issue('invalid_commencement_legal_record', "Firm Commencement {$key} contains an unknown, duplicate, or incomplete legal completion record.");
                }
                $completedRequirementTypes[] = $type;
                $completedAt = $this->date($requirementRecord['completed_at'] ?? null);
                if ($completedAt === null) {
                    $conflicts[] = $this->issue('invalid_commencement_legal_record_time', "Firm Commencement {$key} contains an undated legal completion record.");
                } elseif ($latestRequirementAt === null || $completedAt->isAfter($latestRequirementAt)) {
                    $latestRequirementAt = $completedAt;
                }
                if (! $this->hasEvidence($requirementRecord['evidence_record_key'] ?? null, $evidence)) {
                    $evidenceGaps[] = $this->issue('missing_commencement_legal_evidence', "Firm Commencement {$key} lacks Evidence for legal requirement {$type}.");
                }
            }
            if (! $this->sameValues($completedRequirementTypes, $requiredRecordTypes)) {
                $legalGaps[] = $this->issue('incomplete_commencement_legal_requirements', "Firm Commencement {$key} does not evidence every and only the counsel-confirmed legal requirements.");
            }

            if (! $this->sameValues(array_values($record['capital_initialization_reference_keys'] ?? []), $capitalReferenceKeys)) {
                $conflicts[] = $this->issue('commencement_capital_snapshot_mismatch', "Firm Commencement {$key} contradicts resolved capital initialization references.");
            }

            $confirmedAt = $this->date($record['confirmed_at'] ?? null);
            if ($confirmedAt === null
                || $confirmedAt->isAfter($resolvedAsOf)
                || ($instrumentExecutedAt !== null && $confirmedAt->isBefore($instrumentExecutedAt))
                || ($latestRequirementAt !== null && $confirmedAt->isBefore($latestRequirementAt))) {
                $conflicts[] = $this->issue('invalid_commencement_confirmation_time', "Firm Commencement {$key} has an invalid confirmation chronology.");
            }
            if (! isset($founders[$record['recorded_by_identity_key'] ?? '']) || ! $this->hasEvidence($record['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_commencement_record_evidence', "Firm Commencement {$key} lacks Founding Partner attribution or complete Evidence.");
            }

            $verified = $globalPrerequisitesResolved
                && $this->issueCount($conflicts, $formationGaps, $legalGaps, $capitalGaps, $evidenceGaps, $counselReview) === $issueCount;
            $resolvedRecords[] = [...$record, 'commencement_verified' => $verified];
            if ($verified && $firmEffectiveAt !== null && ! $firmEffectiveAt->isAfter($resolvedAsOf)) {
                $officeActivationBases[] = [
                    'key' => $key.'::formation-derived-assignments',
                    'source_type' => 'formation_commencement',
                    'commencement_record_key' => $key,
                    'effective_at' => $firm['effective_date'],
                    'founding_partner_identity_keys' => $founderKeys,
                    'constitutional_instrument_reference' => $instrument['repository_reference'],
                    'evidence_record_key' => $record['evidence_record_key'],
                    'permits_formation_derived_assignments' => true,
                ];
            }
        }

        if ($conflicts !== [] || $formationGaps !== [] || $legalGaps !== [] || $capitalGaps !== [] || $evidenceGaps !== [] || $counselReview !== []) {
            $officeActivationBases = [];
        }

        return new ResolvedFormationCompletion(
            $definition->schemaVersion,
            $definition->requirements,
            $definition->legalRequirementsRule,
            $definition->capitalInitialization,
            $resolvedRecords,
            $officeActivationBases,
            $definition->evidenceRecords,
            $conflicts,
            $formationGaps,
            $legalGaps,
            $capitalGaps,
            $evidenceGaps,
            $counselReview,
        );
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  list<array{code: string, message: string}>  $legalGaps
     * @param  list<array{code: string, message: string}>  $counselReview
     * @return list<string>
     */
    private function legalRequirementTypes(array $rule, array &$legalGaps, array &$counselReview): array
    {
        $types = array_values($rule['required_record_types'] ?? []);
        if (($rule['state'] ?? null) !== 'resolved' || $types === [] || count($types) !== count(array_unique($types))) {
            $legalGaps[] = $this->issue('formation_legal_requirements_unresolved', 'The applicable legal formation and registration requirement set remains unresolved.');
        }
        if (($rule['legal_state'] ?? null) !== 'counsel_confirmed' || empty($rule['counsel_confirmation_reference'])) {
            $counselReview[] = $this->issue('formation_legal_requirements_counsel_review', 'Philippine counsel has not confirmed the applicable formation and registration requirement set.');
        }

        return $types;
    }

    /**
     * @param  array<string, mixed>  $capital
     * @param  array<string, array<string, mixed>>  $founders
     * @param  array<string, array<string, mixed>>  $evidence
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $capitalGaps
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @param  list<array{code: string, message: string}>  $counselReview
     * @return list<string>
     */
    private function capitalReferenceKeys(array $capital, array $founders, array $evidence, array &$conflicts, array &$capitalGaps, array &$evidenceGaps, array &$counselReview): array
    {
        if (($capital['state'] ?? null) !== 'resolved') {
            $capitalGaps[] = $this->issue('initial_capital_contributions_unresolved', 'Initial capital contributions remain explicitly unresolved.');
        }
        if (($capital['legal_state'] ?? null) !== 'counsel_confirmed' || empty($capital['counsel_confirmation_reference'])) {
            $counselReview[] = $this->issue('capital_initialization_counsel_review', 'Philippine counsel and accounting advisers have not confirmed formation capital treatment.');
        }

        $references = [];
        $contributingFounders = [];
        foreach ($capital['contribution_records'] ?? [] as $record) {
            $key = (string) ($record['key'] ?? '');
            $identityKey = (string) ($record['partner_identity_key'] ?? '');
            if ($key === '' || in_array($key, $references, true) || ! isset($founders[$identityKey]) || in_array($identityKey, $contributingFounders, true) || empty($record['contribution_reference'])) {
                $conflicts[] = $this->issue('invalid_initial_capital_record', 'An initial capital record has a missing, duplicate, unknown, or incomplete reference.');
            }
            $references[] = $key;
            $contributingFounders[] = $identityKey;
            if (! $this->hasEvidence($record['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_initial_capital_evidence', "Initial capital record {$key} lacks complete Evidence.");
            }
        }
        if (! $this->sameValues($contributingFounders, array_keys($founders))) {
            $capitalGaps[] = $this->issue('incomplete_founder_capital_initialization', 'Capital initialization does not reference every and only the Founding Partners.');
        }

        return $references;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array<string, array<string, mixed>>
     */
    private function evidenceIndex(array $records, Carbon $asOf, array &$conflicts, array &$evidenceGaps): array
    {
        $index = [];
        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? '');
            if ($key === '' || isset($index[$key])) {
                $conflicts[] = $this->issue('invalid_formation_completion_evidence_key', 'A Formation Completion Evidence Record has a missing or duplicate key.');
            }
            foreach (['record_type', 'subject', 'actor', 'recorded_at', 'source', 'reason', 'state'] as $field) {
                if (empty($record[$field])) {
                    $evidenceGaps[] = $this->issue('incomplete_formation_completion_evidence', "Formation Completion Evidence {$key} is incomplete.");
                    break;
                }
            }
            $recordedAt = $this->date($record['recorded_at'] ?? null);
            if ($recordedAt === null || $recordedAt->isAfter($asOf)) {
                $conflicts[] = $this->issue('invalid_formation_completion_evidence_time', "Formation Completion Evidence {$key} has an invalid recording time.");
            }
            $index[$key] = $record;
        }

        return $index;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array<string, array<string, mixed>>
     */
    private function indexByKey(array $records): array
    {
        $index = [];
        foreach ($records as $record) {
            if (is_string($record['key'] ?? null)) {
                $index[$record['key']] = $record;
            }
        }

        return $index;
    }

    /** @param array<string, array<string, mixed>> $evidence */
    private function hasEvidence(mixed $key, array $evidence): bool
    {
        return is_string($key) && isset($evidence[$key]);
    }

    /**
     * @param  list<string>  $left
     * @param  list<string>  $right
     */
    private function sameValues(array $left, array $right): bool
    {
        sort($left);
        sort($right);

        return $left === $right;
    }

    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /** @param list<array{code: string, message: string}> ...$reports */
    private function issueCount(array ...$reports): int
    {
        return array_sum(array_map(count(...), $reports));
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
