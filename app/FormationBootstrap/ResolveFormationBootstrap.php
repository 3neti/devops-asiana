<?php

namespace App\FormationBootstrap;

use App\Partnership\ResolvedPartnership;
use App\Policies\PolicyRegistryDefinition;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Throwable;

final class ResolveFormationBootstrap
{
    private const array INITIAL_POLICY_VERSIONS = [
        'policy:partnership-governance:0.1',
        'policy:authority-and-delegation:0.1',
    ];

    public function handle(
        FormationBootstrapDefinition $definition,
        ResolvedPartnership $partnership,
        PolicyRegistryDefinition $policies,
        ?DateTimeImmutable $asOf = null,
    ): ResolvedFormationBootstrap {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $formationGaps */
        $formationGaps = [];
        /** @var list<array{code: string, message: string}> $consentGaps */
        $consentGaps = [];
        /** @var list<array{code: string, message: string}> $evidenceGaps */
        $evidenceGaps = [];
        /** @var list<array{code: string, message: string}> $counselReview */
        $counselReview = [];
        $effectiveAt = Carbon::instance($asOf ?? new DateTimeImmutable);
        $founders = $this->indexByKey($partnership->formation['founding_partners'] ?? []);
        $policyVersions = $this->policyVersionIndex($policies);
        $eligiblePolicies = $this->eligiblePolicyIndex($definition->eligiblePolicyVersions, $policyVersions, $conflicts);
        $evidence = $this->evidenceIndex($definition->evidenceRecords, $conflicts, $evidenceGaps);
        $firmEffectiveAt = $this->date($partnership->formation['firm']['effective_date'] ?? null);

        if ($firmEffectiveAt === null) {
            $formationGaps[] = $this->issue('formation_effective_date_unresolved', 'The Firm effective date remains unresolved; formation ratification cannot become operative.');
        }
        if (($definition->consentRule['state'] ?? null) !== 'resolved' || ($definition->consentRule['method'] ?? null) !== 'unanimous_founding_partners') {
            $consentGaps[] = $this->issue('formation_consent_rule_unresolved', 'The constitutional consent rule for initial ratification remains unresolved.');
        }
        if (($definition->consentRule['legal_state'] ?? null) !== 'counsel_confirmed'
            || empty($definition->consentRule['counsel_confirmation_reference'])) {
            $counselReview[] = $this->issue('formation_consent_rule_counsel_review', 'Philippine counsel has not confirmed the legal implementation of the initial ratification mechanism.');
        }
        if ($definition->ratificationRecords === []) {
            $formationGaps[] = $this->issue('formation_ratification_not_recorded', 'No executed Formation Ratification Record exists.');
        }

        $constitutionalPrerequisitesResolved = $conflicts === []
            && $formationGaps === []
            && $consentGaps === []
            && $evidenceGaps === []
            && $counselReview === [];
        $ratifications = [];
        $policyApprovals = [];
        $ratificationKeys = [];
        foreach ($definition->ratificationRecords as $record) {
            $issueCount = count($conflicts) + count($formationGaps) + count($consentGaps) + count($evidenceGaps) + count($counselReview);
            $key = (string) ($record['key'] ?? '');
            if ($key === '' || in_array($key, $ratificationKeys, true)) {
                $conflicts[] = $this->issue('invalid_formation_ratification_key', 'A Formation Ratification Record has a missing or duplicate key.');
            }
            $ratificationKeys[] = $key;
            if (($record['status'] ?? null) !== 'ratified') {
                $formationGaps[] = $this->issue('formation_ratification_incomplete', "Formation Ratification {$key} is not Ratified.");
            }

            $instrument = is_array($record['formation_instrument'] ?? null) ? $record['formation_instrument'] : [];
            $instrumentEffectiveAt = $this->date($instrument['effective_at'] ?? null);
            $instrumentExecutedAt = $this->date($instrument['executed_at'] ?? null);
            if (($instrument['type'] ?? null) !== 'partnership_agreement'
                || empty($instrument['repository_reference'])
                || empty($instrument['content_digest'])
                || ($instrument['counsel_confirmed'] ?? false) !== true
                || empty($instrument['counsel_confirmation_reference'])) {
                $formationGaps[] = $this->issue('incomplete_formation_instrument', "Formation Ratification {$key} lacks a counsel-confirmed executed Partnership Agreement reference.");
            }
            if (($instrument['counsel_confirmation_reference'] ?? null) !== ($definition->consentRule['counsel_confirmation_reference'] ?? null)) {
                $conflicts[] = $this->issue('formation_counsel_confirmation_mismatch', "Formation Ratification {$key} does not cite the confirmed constitutional consent advice.");
            }
            if ($instrumentExecutedAt === null || $instrumentEffectiveAt === null || ($firmEffectiveAt !== null && ! $instrumentEffectiveAt->equalTo($firmEffectiveAt))) {
                $conflicts[] = $this->issue('formation_instrument_date_mismatch', "Formation Ratification {$key} does not match the resolved Firm effective date.");
            }
            if (! $this->hasEvidence($instrument['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_formation_instrument_evidence', "Formation Ratification {$key} lacks complete instrument Evidence.");
            }

            $consentIdentityKeys = [];
            foreach ($record['founding_partner_consents'] ?? [] as $consent) {
                $identityKey = (string) ($consent['identity_key'] ?? '');
                if (! isset($founders[$identityKey]) || in_array($identityKey, $consentIdentityKeys, true) || ($consent['decision'] ?? null) !== 'consent') {
                    $conflicts[] = $this->issue('invalid_founder_ratification_consent', "Formation Ratification {$key} contains an unknown, duplicate, or non-consenting founder record.");
                }
                $consentIdentityKeys[] = $identityKey;
                $signedAt = $this->date($consent['signed_at'] ?? null);
                if ($signedAt === null || ($instrumentExecutedAt !== null && $signedAt->isAfter($instrumentExecutedAt))) {
                    $conflicts[] = $this->issue('invalid_founder_consent_time', "Formation Ratification {$key} contains a founder consent outside the instrument execution chronology.");
                }
                if (! $this->hasEvidence($consent['evidence_record_key'] ?? null, $evidence)) {
                    $evidenceGaps[] = $this->issue('missing_founder_consent_evidence', "Formation Ratification {$key} lacks complete consent Evidence for {$identityKey}.");
                }
            }
            if (array_diff(array_keys($founders), $consentIdentityKeys) !== [] || array_diff($consentIdentityKeys, array_keys($founders)) !== []) {
                $consentGaps[] = $this->issue('incomplete_unanimous_founder_consent', "Formation Ratification {$key} does not preserve explicit consent from every Founding Partner.");
            }

            $approvalTargets = [];
            foreach ($record['initial_policy_approvals'] ?? [] as $approval) {
                $targetKey = $this->policyVersionKey((string) ($approval['policy_key'] ?? ''), (string) ($approval['policy_version'] ?? ''));
                $eligible = $eligiblePolicies[$targetKey] ?? null;
                if ($eligible === null || in_array($targetKey, $approvalTargets, true)) {
                    $conflicts[] = $this->issue('invalid_bootstrap_policy_approval', "Formation Ratification {$key} contains an unknown, ineligible, or duplicate initial Policy Version.");
                } elseif (($approval['document_path'] ?? null) !== $eligible['document_path'] || ($approval['content_digest'] ?? null) !== $eligible['content_digest']) {
                    $conflicts[] = $this->issue('bootstrap_policy_content_mismatch', "Formation Ratification {$key} contradicts controlled content for {$targetKey}.");
                }
                $approvalTargets[] = $targetKey;
            }
            if (array_diff(array_keys($eligiblePolicies), $approvalTargets) !== [] || array_diff($approvalTargets, array_keys($eligiblePolicies)) !== []) {
                $formationGaps[] = $this->issue('incomplete_initial_policy_ratification', "Formation Ratification {$key} does not approve every and only eligible initial Policy Version.");
            }

            $ratifiedAt = $this->date($record['ratified_at'] ?? null);
            if ($ratifiedAt === null || $ratifiedAt->isAfter($effectiveAt) || ($instrumentExecutedAt !== null && $ratifiedAt->isBefore($instrumentExecutedAt))) {
                $conflicts[] = $this->issue('invalid_formation_ratification_time', "Formation Ratification {$key} has an invalid ratification time.");
            }
            if (empty($record['recorded_by_identity_key']) || ! $this->hasEvidence($record['evidence_record_key'] ?? null, $evidence)) {
                $evidenceGaps[] = $this->issue('missing_formation_ratification_evidence', "Formation Ratification {$key} lacks attribution or complete Evidence.");
            }

            $verified = $constitutionalPrerequisitesResolved
                && count($conflicts) + count($formationGaps) + count($consentGaps) + count($evidenceGaps) + count($counselReview) === $issueCount;
            $ratifications[] = [...$record, 'ratification_verified' => $verified];
            if ($verified) {
                foreach ($approvalTargets as $targetKey) {
                    $policyApprovals[] = [
                        'key' => $key.'::'.$targetKey,
                        'source_type' => 'formation_ratification',
                        'ratification_record_key' => $key,
                        'target_key' => $targetKey,
                        'founding_partner_identity_keys' => $consentIdentityKeys,
                        'formation_instrument_reference' => $instrument['repository_reference'],
                        'ratified_at' => $record['ratified_at'],
                        'evidence_record_key' => $record['evidence_record_key'],
                        'grants_initial_policy_approval_basis' => true,
                    ];
                }
            }
        }

        if ($conflicts !== [] || $formationGaps !== [] || $consentGaps !== [] || $evidenceGaps !== [] || $counselReview !== []) {
            $policyApprovals = [];
        }

        return new ResolvedFormationBootstrap(
            $definition->schemaVersion,
            $definition->requirements,
            $definition->eligiblePolicyVersions,
            $definition->consentRule,
            $ratifications,
            $policyApprovals,
            $definition->evidenceRecords,
            $conflicts,
            $formationGaps,
            $consentGaps,
            $evidenceGaps,
            $counselReview,
        );
    }

    /**
     * @param  list<array<string, string>>  $eligiblePolicies
     * @param  array<string, array<string, mixed>>  $policyVersions
     * @param  list<array{code: string, message: string}>  $conflicts
     * @return array<string, array<string, mixed>>
     */
    private function eligiblePolicyIndex(array $eligiblePolicies, array $policyVersions, array &$conflicts): array
    {
        $index = [];
        foreach ($eligiblePolicies as $eligible) {
            $targetKey = $this->policyVersionKey($eligible['policy_key'], $eligible['policy_version']);
            $policy = $policyVersions[$targetKey] ?? null;
            if ($policy === null || isset($index[$targetKey])) {
                $conflicts[] = $this->issue('invalid_eligible_bootstrap_policy', "Eligible bootstrap Policy Version {$targetKey} is missing or duplicated.");

                continue;
            }
            $index[$targetKey] = $policy;
        }
        if (array_diff(self::INITIAL_POLICY_VERSIONS, array_keys($index)) !== []
            || array_diff(array_keys($index), self::INITIAL_POLICY_VERSIONS) !== []) {
            $conflicts[] = $this->issue('invalid_bootstrap_policy_allowlist', 'Formation bootstrap eligibility must contain every and only the two initial governance Policy Versions.');
        }

        return $index;
    }

    /** @return array<string, array<string, mixed>> */
    private function policyVersionIndex(PolicyRegistryDefinition $policies): array
    {
        $index = [];
        foreach ($policies->policies as $policy) {
            foreach ($policy->versions as $version) {
                $key = $this->policyVersionKey($policy->key, (string) ($version['version'] ?? ''));
                $index[$key] = [...$version, 'policy_key' => $policy->key];
            }
        }

        return $index;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, message: string}>  $evidenceGaps
     * @return array<string, array<string, mixed>>
     */
    private function evidenceIndex(array $records, array &$conflicts, array &$evidenceGaps): array
    {
        $index = [];
        foreach ($records as $record) {
            $key = (string) ($record['key'] ?? '');
            if ($key === '' || isset($index[$key])) {
                $conflicts[] = $this->issue('invalid_bootstrap_evidence_key', 'A Formation Bootstrap Evidence Record has a missing or duplicate key.');
            }
            foreach (['record_type', 'subject', 'actor', 'recorded_at', 'source', 'reason', 'state'] as $field) {
                if (empty($record[$field])) {
                    $evidenceGaps[] = $this->issue('incomplete_bootstrap_evidence', "Formation Bootstrap Evidence {$key} is incomplete.");
                    break;
                }
            }
            $index[$key] = $record;
        }

        return $index;
    }

    /** @param array<string, array<string, mixed>> $evidence */
    private function hasEvidence(mixed $key, array $evidence): bool
    {
        return is_string($key) && isset($evidence[$key]);
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

    private function policyVersionKey(string $policyKey, string $version): string
    {
        return "policy:{$policyKey}:{$version}";
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

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
