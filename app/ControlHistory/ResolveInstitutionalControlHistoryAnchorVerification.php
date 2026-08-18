<?php

namespace App\ControlHistory;

final class ResolveInstitutionalControlHistoryAnchorVerification
{
    public function handle(
        InstitutionalControlHistoryAnchorVerificationDefinition $definition,
        ResolvedInstitutionalControlHistoryIntegrity $integrity,
    ): ResolvedInstitutionalControlHistoryAnchorVerification {
        /** @var list<array{code: string, message: string}> $conflicts */
        $conflicts = [];
        /** @var list<array{code: string, message: string}> $verificationGaps */
        $verificationGaps = [];

        if ($definition->source !== $integrity->integrityKey) {
            $conflicts[] = $this->issue('verification_source_mismatch', 'Anchor verification does not identify the resolved integrity projection.');
        }
        if ($definition->algorithm !== $integrity->algorithm) {
            $conflicts[] = $this->issue('verification_algorithm_mismatch', 'Anchor verification algorithm does not match the resolved integrity algorithm.');
        }
        foreach ($integrity->conflicts as $conflict) {
            $verificationGaps[] = $this->issue('integrity_conflict', $conflict['code'].': '.$conflict['message']);
        }
        foreach ($integrity->integrityGaps as $gap) {
            $verificationGaps[] = $this->issue('integrity_gap', $gap['code'].': '.$gap['message']);
        }

        if ($definition->suppliedHistoryAnchor === null) {
            $verificationGaps[] = $this->issue('history_anchor_not_supplied', 'No supplied history anchor is available for comparison.');
        } elseif (! preg_match('/^[a-f0-9]{64}$/', $definition->suppliedHistoryAnchor)) {
            $verificationGaps[] = $this->issue('invalid_supplied_history_anchor', 'Supplied history anchor must be a lowercase SHA-256 digest.');
        } elseif (! hash_equals($integrity->historyAnchor, $definition->suppliedHistoryAnchor)) {
            $verificationGaps[] = $this->issue('history_anchor_mismatch', 'Supplied history anchor does not match resolved chronology.');
        }

        $suppliedByKey = [];
        foreach ($definition->suppliedEventAnchors as $supplied) {
            $key = (string) ($supplied['event_key'] ?? '');
            if ($key === '' || ! is_string($supplied['anchor'] ?? null)) {
                $verificationGaps[] = $this->issue('invalid_supplied_event_anchor', 'A supplied event anchor lacks a key or digest.');

                continue;
            }
            if (isset($suppliedByKey[$key])) {
                $conflicts[] = $this->issue('duplicate_supplied_event_anchor', "Supplied event anchor {$key} occurs more than once.");
            }
            $suppliedByKey[$key] = $supplied['anchor'];
        }

        if ($definition->suppliedHistoryAnchor !== null && $integrity->eventAnchors !== [] && $definition->suppliedEventAnchors === []) {
            $verificationGaps[] = $this->issue('event_anchors_not_supplied', 'A history anchor was supplied without its event-anchor set.');
        }

        $eventComparisons = [];
        foreach ($integrity->eventAnchors as $event) {
            $key = (string) ($event['event_key'] ?? '');
            $expected = (string) ($event['anchor'] ?? '');
            $supplied = $suppliedByKey[$key] ?? null;
            $comparison = [
                'event_key' => $key,
                'resolved_anchor' => $expected,
                'supplied_anchor' => $supplied,
                'status' => $supplied === null ? 'not_supplied' : (hash_equals($expected, $supplied) ? 'matched' : 'mismatch'),
            ];
            if ($comparison['status'] === 'not_supplied') {
                $verificationGaps[] = $this->issue('event_anchor_not_supplied', "No supplied anchor exists for history event {$key}.");
            } elseif ($comparison['status'] === 'mismatch') {
                $verificationGaps[] = $this->issue('event_anchor_mismatch', "Supplied anchor does not match history event {$key}.");
            }
            $eventComparisons[] = $comparison;
            unset($suppliedByKey[$key]);
        }
        foreach (array_keys($suppliedByKey) as $key) {
            $verificationGaps[] = $this->issue('unexpected_supplied_event_anchor', "Supplied anchor {$key} is not present in resolved history.");
        }

        return new ResolvedInstitutionalControlHistoryAnchorVerification(
            schemaVersion: $definition->schemaVersion,
            verificationKey: $definition->verificationKey,
            source: $definition->source,
            algorithm: $definition->algorithm,
            suppliedHistoryAnchor: $definition->suppliedHistoryAnchor,
            resolvedHistoryAnchor: $integrity->historyAnchor,
            eventComparisons: $eventComparisons,
            conflicts: $conflicts,
            verificationGaps: $verificationGaps,
        );
    }

    /** @return array{code: string, message: string} */
    private function issue(string $code, string $message): array
    {
        return compact('code', 'message');
    }
}
