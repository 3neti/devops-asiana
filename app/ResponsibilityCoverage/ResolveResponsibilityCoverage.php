<?php

namespace App\ResponsibilityCoverage;

use App\Partnership\ResolvedPartnership;
use App\Policies\PolicyLifecycleStatus;
use App\Policies\ResolvedPolicyRegistry;

final class ResolveResponsibilityCoverage
{
    public function handle(
        ResponsibilityCoverageDefinition $definition,
        ResolvedPartnership $partnership,
        ResolvedPolicyRegistry $policyRegistry,
    ): ResolvedResponsibilityCoverage {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, requirement_key: string, message: string}> $vacancies */
        $vacancies = [];
        /** @var list<array{code: string, requirement_key: string, message: string}> $qualificationGaps */
        $qualificationGaps = [];
        /** @var list<array{code: string, requirement_key: string, message: string}> $successionGaps */
        $successionGaps = [];
        /** @var list<array{code: string, requirement_key: string, message: string}> $pendingRequirements */
        $pendingRequirements = [];
        $coverageCounts = array_fill_keys(['covered', 'vacant', 'pending_activation', 'conflicted'], 0);
        $partners = $this->indexByKey($partnership->formation['founding_partners'] ?? []);
        $offices = $this->indexByKey($partnership->constitution['offices'] ?? []);
        $responsibilities = $this->indexByKey($partnership->constitution['responsibility_assignments'] ?? []);
        $policies = $this->indexByKey($policyRegistry->policies);
        $requirementKeys = [];
        $resolvedRequirements = [];

        foreach ($definition->requirements as $requirement) {
            $resolved = $this->resolveRequirement(
                requirement: $requirement,
                partners: $partners,
                offices: $offices,
                responsibilities: $responsibilities,
                policies: $policies,
                requirementKeys: $requirementKeys,
                conflicts: $conflicts,
                vacancies: $vacancies,
                qualificationGaps: $qualificationGaps,
                successionGaps: $successionGaps,
                pendingRequirements: $pendingRequirements,
            );
            $coverageCounts[$resolved['coverage_status']]++;
            $resolvedRequirements[] = $resolved;
        }

        $resolvedByKey = $this->indexByKey($resolvedRequirements);
        [$separationConstraints, $separationConflicts] = $this->resolveSeparationConstraints(
            $definition->separationConstraints,
            $resolvedByKey,
            $conflicts,
        );
        $concentrationExposures = $this->detectConcentration(
            $resolvedRequirements,
            $partners,
            $definition->concentrationReviewThreshold,
        );

        return new ResolvedResponsibilityCoverage(
            schemaVersion: $definition->schemaVersion,
            concentrationReviewThreshold: $definition->concentrationReviewThreshold,
            requirements: $resolvedRequirements,
            separationConstraints: $separationConstraints,
            coverageCounts: $coverageCounts,
            conflicts: $conflicts,
            vacancies: $vacancies,
            qualificationGaps: $qualificationGaps,
            successionGaps: $successionGaps,
            concentrationExposures: $concentrationExposures,
            separationConflicts: $separationConflicts,
            pendingRequirements: $pendingRequirements,
        );
    }

    /**
     * @param  array<string, mixed>  $requirement
     * @param  array<string, array<string, mixed>>  $partners
     * @param  array<string, array<string, mixed>>  $offices
     * @param  array<string, array<string, mixed>>  $responsibilities
     * @param  array<string, array<string, mixed>>  $policies
     * @param  list<string>  $requirementKeys
     * @param  list<array{code: string, message: string}>  $conflicts
     * @param  list<array{code: string, requirement_key: string, message: string}>  $vacancies
     * @param  list<array{code: string, requirement_key: string, message: string}>  $qualificationGaps
     * @param  list<array{code: string, requirement_key: string, message: string}>  $successionGaps
     * @param  list<array{code: string, requirement_key: string, message: string}>  $pendingRequirements
     *
     * @param-out list<string> $requirementKeys
     * @param-out list<array{code: string, message: string}> $conflicts
     * @param-out list<array{code: string, requirement_key: string, message: string}> $vacancies
     * @param-out list<array{code: string, requirement_key: string, message: string}> $qualificationGaps
     * @param-out list<array{code: string, requirement_key: string, message: string}> $successionGaps
     * @param-out list<array{code: string, requirement_key: string, message: string}> $pendingRequirements
     *
     * @return array<string, mixed>
     */
    private function resolveRequirement(
        array $requirement,
        array $partners,
        array $offices,
        array $responsibilities,
        array $policies,
        array &$requirementKeys,
        array &$conflicts,
        array &$vacancies,
        array &$qualificationGaps,
        array &$successionGaps,
        array &$pendingRequirements,
    ): array {
        $key = (string) ($requirement['key'] ?? '');
        $label = (string) ($requirement['label'] ?? $key);

        if ($key === '' || in_array($key, $requirementKeys, true)) {
            $conflicts[] = $this->issue('invalid_requirement_key', 'A Responsibility Coverage requirement has a missing or duplicate key.');
        }
        $requirementKeys[] = $key;

        [$sourceStatus, $sourceLabel, $sourceValid] = $this->resolveSource($requirement['source'] ?? null, $policies, $label, $conflicts);
        [$holderKeys, $holderSourceValid] = $this->resolveHolders($requirement['holder_source'] ?? null, $offices, $responsibilities, $label, $conflicts);
        $knownHolderKeys = array_values(array_filter(
            $holderKeys,
            static fn (string $holderKey): bool => isset($partners[$holderKey]),
        ));
        $unknownHolderKeys = array_values(array_diff($holderKeys, $knownHolderKeys));

        foreach ($unknownHolderKeys as $unknownHolderKey) {
            $conflicts[] = $this->issue('unknown_responsibility_holder', "{$label} refers to unknown holder {$unknownHolderKey}.");
        }

        $qualifiedStatuses = is_array($requirement['qualified_partner_statuses'] ?? null)
            ? array_values($requirement['qualified_partner_statuses'])
            : [];
        $unqualifiedHolderKeys = array_values(array_filter(
            $knownHolderKeys,
            static fn (string $holderKey): bool => $qualifiedStatuses !== []
                && ! in_array($partners[$holderKey]['partner_status'] ?? null, $qualifiedStatuses, true),
        ));

        if ($sourceStatus === 'operative') {
            foreach ($unqualifiedHolderKeys as $unqualifiedHolderKey) {
                $qualificationGaps[] = [
                    'code' => 'unqualified_responsibility_holder',
                    'requirement_key' => $key,
                    'message' => "{$partners[$unqualifiedHolderKey]['name']} does not satisfy the recorded qualification for {$label}.",
                ];
            }
        }

        $minimumHolders = (int) ($requirement['required_holders']['minimum'] ?? 0);
        $maximumHolders = $requirement['required_holders']['maximum'] ?? null;
        $maximumExceeded = is_int($maximumHolders) && count($knownHolderKeys) > $maximumHolders;
        $attachmentValid = $this->attachmentMatchesSource($requirement);

        if (! $attachmentValid) {
            $conflicts[] = $this->issue('authority_attachment_mismatch', "{$label} attaches authority differently from its holder source.");
        }
        if ($maximumExceeded) {
            $conflicts[] = $this->issue('responsibility_cardinality_exceeded', "{$label} has more holders than its recorded maximum.");
        }

        $coverageStatus = match (true) {
            $sourceStatus !== 'operative' && $sourceValid => 'pending_activation',
            ! $sourceValid, ! $holderSourceValid, $unknownHolderKeys !== [], $maximumExceeded, ! $attachmentValid => 'conflicted',
            count($knownHolderKeys) < $minimumHolders => 'vacant',
            $unqualifiedHolderKeys !== [] => 'conflicted',
            default => 'covered',
        };

        if ($coverageStatus === 'vacant') {
            $vacancies[] = [
                'code' => ($requirement['category'] ?? null) === 'office' ? 'required_office_vacant' : 'required_responsibility_vacant',
                'requirement_key' => $key,
                'message' => "{$label} requires at least {$minimumHolders} qualified holder(s) and currently has ".count($knownHolderKeys).'.',
            ];
        }
        if ($coverageStatus === 'pending_activation') {
            $pendingRequirements[] = [
                'code' => 'requirement_pending_source_activation',
                'requirement_key' => $key,
                'message' => "{$label} remains a design requirement until {$sourceLabel} becomes operative.",
            ];
        }

        $succession = is_array($requirement['succession'] ?? null) ? $requirement['succession'] : [];
        $alternateHolderKeys = is_array($succession['alternate_holder_keys'] ?? null)
            ? array_values($succession['alternate_holder_keys'])
            : [];
        $validAlternates = array_values(array_filter(
            $alternateHolderKeys,
            static fn (string $holderKey): bool => isset($partners[$holderKey])
                && ! in_array($holderKey, $knownHolderKeys, true)
                && ($qualifiedStatuses === [] || in_array($partners[$holderKey]['partner_status'] ?? null, $qualifiedStatuses, true)),
        ));

        if ($sourceStatus === 'operative' && $coverageStatus === 'covered' && ($succession['required'] ?? false) === true && $validAlternates === []) {
            $successionGaps[] = [
                'code' => 'missing_succession_coverage',
                'requirement_key' => $key,
                'message' => "{$label} is currently covered but has no distinct qualified alternate.",
            ];
        }

        return [
            ...$requirement,
            'source_status' => $sourceStatus,
            'source_label' => $sourceLabel,
            'holder_keys' => $knownHolderKeys,
            'holder_names' => array_map(
                static fn (string $holderKey): string => (string) $partners[$holderKey]['name'],
                $knownHolderKeys,
            ),
            'alternate_holder_keys' => $validAlternates,
            'alternate_holder_names' => array_map(
                static fn (string $holderKey): string => (string) $partners[$holderKey]['name'],
                $validAlternates,
            ),
            'coverage_status' => $coverageStatus,
            'sole_holder' => $coverageStatus === 'covered' && count($knownHolderKeys) === 1,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $policies
     * @param  list<array{code: string, message: string}>  $conflicts
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     *
     * @return array{string, string, bool}
     */
    private function resolveSource(mixed $source, array $policies, string $label, array &$conflicts): array
    {
        if (! is_array($source)) {
            $conflicts[] = $this->issue('missing_requirement_source', "{$label} lacks a governing source.");

            return ['missing', 'missing source', false];
        }
        if (($source['type'] ?? null) === 'constitution') {
            $reference = (string) ($source['reference'] ?? '');

            return $reference === ''
                ? ['missing', 'missing constitutional reference', false]
                : ['operative', $reference, true];
        }
        if (($source['type'] ?? null) !== 'policy') {
            $conflicts[] = $this->issue('invalid_requirement_source', "{$label} has an unsupported governing source.");

            return ['invalid', 'invalid source', false];
        }

        $policyKey = (string) ($source['key'] ?? '');
        $versionNumber = (string) ($source['version'] ?? '');
        $policy = $policies[$policyKey] ?? null;
        if ($policy === null) {
            $conflicts[] = $this->issue('unknown_requirement_policy', "{$label} references unknown policy {$policyKey}.");

            return ['missing', "{$policyKey} {$versionNumber}", false];
        }
        $version = null;
        foreach ($policy['versions'] ?? [] as $candidateVersion) {
            if (is_array($candidateVersion) && ($candidateVersion['version'] ?? null) === $versionNumber) {
                $version = $candidateVersion;

                break;
            }
        }
        if (! is_array($version)) {
            $conflicts[] = $this->issue('unknown_requirement_policy_version', "{$label} references missing {$policyKey} version {$versionNumber}.");

            return ['missing', "{$policy['title']} {$versionNumber}", false];
        }

        $status = PolicyLifecycleStatus::tryFrom((string) ($version['status'] ?? ''));
        $sourceLabel = "{$policy['title']} {$versionNumber}";
        if ($status === null) {
            return ['invalid', $sourceLabel, false];
        }

        return [$status === PolicyLifecycleStatus::Effective ? 'operative' : $status->value, $sourceLabel, true];
    }

    /**
     * @param  array<string, array<string, mixed>>  $offices
     * @param  array<string, array<string, mixed>>  $responsibilities
     * @param  list<array{code: string, message: string}>  $conflicts
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     *
     * @return array{list<string>, bool}
     */
    private function resolveHolders(mixed $holderSource, array $offices, array $responsibilities, string $label, array &$conflicts): array
    {
        if (! is_array($holderSource)) {
            $conflicts[] = $this->issue('missing_holder_source', "{$label} lacks a holder source.");

            return [[], false];
        }
        $type = $holderSource['type'] ?? null;
        if ($type === 'unassigned') {
            return [[], true];
        }
        $key = (string) ($holderSource['key'] ?? '');
        if ($type === 'office') {
            $office = $offices[$key] ?? null;
            if ($office === null) {
                $conflicts[] = $this->issue('unknown_holder_office', "{$label} references unknown office {$key}.");

                return [[], false];
            }

            return [is_string($office['holder'] ?? null) ? [$office['holder']] : [], true];
        }
        if ($type === 'responsibility') {
            $responsibility = $responsibilities[$key] ?? null;
            if ($responsibility === null) {
                $conflicts[] = $this->issue('unknown_holder_responsibility', "{$label} references unknown responsibility {$key}.");

                return [[], false];
            }

            return [array_values($responsibility['holders'] ?? []), true];
        }

        $conflicts[] = $this->issue('invalid_holder_source', "{$label} has an unsupported holder source.");

        return [[], false];
    }

    /** @param array<string, mixed> $requirement */
    private function attachmentMatchesSource(array $requirement): bool
    {
        $holderSourceType = $requirement['holder_source']['type'] ?? null;
        $attachment = $requirement['authority_attachment'] ?? null;

        return match ($holderSourceType) {
            'office' => $attachment === 'office',
            'responsibility' => in_array($attachment, ['partner_status', 'professional_role', 'delegation', 'none'], true),
            'unassigned' => in_array($attachment, ['professional_role', 'delegation'], true),
            default => false,
        };
    }

    /**
     * @param  list<array{key: string, left_requirement_key: string, right_requirement_key: string, reason: string}>  $constraints
     * @param  array<string, array<string, mixed>>  $requirements
     * @param  list<array{code: string, message: string}>  $conflicts
     *
     * @param-out list<array{code: string, message: string}> $conflicts
     *
     * @return array{list<array<string, mixed>>, list<array{code: string, constraint_key: string, holder_keys: list<string>, message: string}>}
     */
    private function resolveSeparationConstraints(array $constraints, array $requirements, array &$conflicts): array
    {
        $resolved = [];
        $separationConflicts = [];
        $constraintKeys = [];

        foreach ($constraints as $constraint) {
            $key = $constraint['key'];
            if (in_array($key, $constraintKeys, true)) {
                $conflicts[] = $this->issue('duplicate_separation_constraint', "Separation constraint {$key} is duplicated.");
            }
            $constraintKeys[] = $key;
            $left = $requirements[$constraint['left_requirement_key']] ?? null;
            $right = $requirements[$constraint['right_requirement_key']] ?? null;
            if ($left === null || $right === null) {
                $conflicts[] = $this->issue('unknown_separation_requirement', "Separation constraint {$key} references an unknown requirement.");
                $resolved[] = [...$constraint, 'status' => 'invalid', 'overlapping_holder_keys' => []];

                continue;
            }
            $operative = $left['source_status'] === 'operative' && $right['source_status'] === 'operative';
            $overlap = $operative
                ? array_values(array_intersect($left['holder_keys'], $right['holder_keys']))
                : [];
            $status = match (true) {
                ! $operative => 'pending_activation',
                $overlap !== [] => 'violated',
                default => 'satisfied',
            };
            if ($overlap !== []) {
                $separationConflicts[] = [
                    'code' => 'prohibited_responsibility_combination',
                    'constraint_key' => $key,
                    'holder_keys' => $overlap,
                    'message' => "{$constraint['reason']} The same holder is currently assigned to both requirements.",
                ];
            }
            $resolved[] = [...$constraint, 'status' => $status, 'overlapping_holder_keys' => $overlap];
        }

        return [$resolved, $separationConflicts];
    }

    /**
     * @param  list<array<string, mixed>>  $requirements
     * @param  array<string, array<string, mixed>>  $partners
     * @return list<array{code: string, holder_key: string, holder_name: string, requirement_keys: list<string>, message: string}>
     */
    private function detectConcentration(array $requirements, array $partners, int $threshold): array
    {
        /** @var array<string, list<string>> $assignments */
        $assignments = [];

        foreach ($requirements as $requirement) {
            if ($requirement['coverage_status'] !== 'covered' || ($requirement['concentration_review'] ?? false) !== true || count($requirement['holder_keys']) !== 1) {
                continue;
            }
            $assignments[$requirement['holder_keys'][0]][] = $requirement['key'];
        }

        $exposures = [];
        foreach ($assignments as $holderKey => $requirementKeys) {
            if (count($requirementKeys) < $threshold) {
                continue;
            }
            $holderName = (string) ($partners[$holderKey]['name'] ?? $holderKey);
            $exposures[] = [
                'code' => 'sole_holder_concentration_review',
                'holder_key' => $holderKey,
                'holder_name' => $holderName,
                'requirement_keys' => $requirementKeys,
                'message' => "{$holderName} is the sole holder for ".count($requirementKeys).' material requirements; continuity and separation should be reviewed.',
            ];
        }

        return $exposures;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array<string, array<string, mixed>>
     */
    private function indexByKey(array $records): array
    {
        $indexed = [];

        foreach ($records as $record) {
            if (is_string($record['key'] ?? null)) {
                $indexed[$record['key']] = $record;
            }
        }

        return $indexed;
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
